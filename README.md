# Generics in PHP
For 15 years I watched people discussing generics in PHP.
[Anthony Ferrara](https://wiki.php.net/rfc/protocol_type_hinting),
[Elliot Levin](https://github.com/TimeToogo/PHP-Generics),
[Nikita Popov](https://github.com/PHPGenerics/php-generics-rfc/issues/45),
[Anton Sukhachev](https://github.com/mrsuh/php-generics),
and others were trying on and on.

In 2023 PHP Foundation funded 
[a year of research](https://thephp.foundation/blog/2024/08/19/state-of-generics-and-collections/) 
in generics implementation. I believe, a complex perfect solution was never a PHP way, though.

PHP changed a lot over the years, and the old solutions may actually work now, with little tweaks.

This is a partial implementation of generics following the early ideas.  
To evaluate, please clone the repo, and run `composer install`.

With this solution I hope to convince people that generics programming is actually possible now. 
And hope to see a partial implementation natively in PHP some day.
 
### Why do you need generics
Data obtained from databases, APIs, and other people code sometimes contain unexpected structure. 
We write lots of checks, but it’s tedious and error-prone.
For single-value variables we define types of parameters to ensure the data comes as expected. 
Generics allow to define types for collections.
Without generics, data sets are either arrays, allowing any data, or you have to define a separate class
with the same functionality for each set of records.
Generics let us write a template class once, and define a concrete type when we make an instance.
This allows ensuring the structure of the data, and skip endless checks for NULL and FALSE.

### Syntax
First, call `new \Generics\Enable();` before the generic functionality is used.

Let's define a wildcard template declaration. Of course, it can be accompanied by the PHPDoc tags for static analysis.
```php
/**
 * @template T
 */
#[\Generics\T]
class Collection extends \ArrayObject{
    use \Generics\GenericTrait;

    /**
     * @param $key
     * @param T $value
     * @return void
     */
    public function offsetSet( $key, #[\Generics\T] $value ){
        parent::offsetSet($key,$value);
    }
}
```

Create an instance of a concrete type:
```php
/** @var Collection<int> $collection */
$collection = Collection::new("int")();
$collection[] = 42;
```

We can use this collection with a concrete type as a parameter:
```php
class Bar{
    /**
    * @param Collection<int> $integers
    * @return int
    */
    public function multiply(#[\Generics\T("Collection<int>")] $integers): int 
    {
        $result = 0;
        for ($integers as $value) {
            $value *= 2;
        }
        return $result;
    }
}
```
When we fill the collection with values from a database or an API data, and miss a check for
a null, false, '' or an empty array, we can be sure the `multiply()` method will not silently return 0.

The code
```php
$foo[] = null;
```
will trigger a TypeError exception with a proper error message and a trace, as PHP natively does.
Now we can skip a type check of each element in a loop over a data set in every method.

Tests [InstantiationTraitTest.php](tests/InstantiationTraitTest.php) and
[InvalidParameterTest.php](tests/InvalidParameterTest.php)
contain some runnable code.

### Why it is possible now
1. Early implementations were slow.
<br><br>
What changed: we have Opcache with inheritance cache in a standard installation now, and lots of memory in servers.
We can use it to avoid performance penalty.  


2. PHPStan and Psalm provide static analysis for generic types defined in comments. They assist a lot, but they don't 
ensure type safety in runtime for the unexpected data.
<br><br>
What changed: PHP is a compiled language with strict types now. We can have type safety for arbitrary
real-life data sets within an application itself, using native PHP functionality.
This is different from validation of the code architecture with a stand-alone static analyser.


3. PHP does not allow symbols < and > as a part of class names, so the code `MyClass<Foo>` becomes
incompatible with PHP syntax.
<br><br>
What changed: we've got Attributes and First-class callables. According to the
[PHP manual](https://www.php.net/manual/en/language.attributes.overview.php):
> With attributes the generic implementation of a feature and its concrete use in an application can be decoupled.

4. It is quite difficult to implement generics for arrays and interfaces. Good engineers
hate incomplete solutions, and we are stuck.
<br><br> 
The major use case for generics is collections of records from a database or an API.
Let's do this part, as it use to happen in PHP.

### How it works
1. When enabled, an autoloader and a "generic" file wrapper are registered. 
2. When a new class is autoloaded, or a template class with a trait is instantiated, 
the loader parses the source code and generates a virtual concrete class for 
a concrete type. The virtual concrete class extends the wildcard class.
Please check the [TemplateDeclarationTest](https://github.com/grikdotnet/generics/blob/master/tests/TemplateDeclarationTest.php) for details of parsing files. 
3. `include()` cals a wrapper that serves the generated code for the virtual class.
4. PHP creates an instance of a concrete virtual class and a Closure that calls a constructor with parameters provided.

### Implemented
* Parsing PHP code that contains generics in an autoloader.
* Generation of a virtual class for a concrete type.
* Altering the source code of the loaded classes. A concrete type defined in the attribute
for a methods parameters is added to parameter declaration.
* A stream wrapper to load virtual classes with include() that uses opcache.
* The \Generics\TypeError exception with the correct error message and a backtrace.

### Can be implemented
* Caching the generated concrete classes in opcache, as well as results of parsing 
of the template classes, and completely avoiding overhead in runtime.
* Generic union types 
* Generic types for property hooks in PHP 8.4
* Generic return types
* Partial support for the final wildcard classes
* Autocompletion for the generic parameters in PHPStorm with a Meta Storm plugin.