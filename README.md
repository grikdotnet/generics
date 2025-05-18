# Generics in PHP
For 15 years people were trying to implement generics in PHP.
[Anthony Ferrara](https://wiki.php.net/rfc/protocol_type_hinting),
[Elliot Levin](https://github.com/TimeToogo/PHP-Generics),
[Nikita Popov](https://github.com/PHPGenerics/php-generics-rfc/issues/45),
[Anton Sukhachev](https://github.com/mrsuh/php-generics),
and others.

In 2023 PHP Foundation funded [a year of research](https://thephp.foundation/blog/2024/08/19/state-of-generics-and-collections/) 
in generics implementation. I believe a complex perfect solution was never a PHP way, though.

PHP changed a lot over the years, and the old approach may actually work now.
This library provides a way to use the generic progrming paradigm in PHP. 

I hope to convince people that generics programming is actually possible now. 
And hope to see a partial implementation natively in PHP some day.

NB: Generic programming has nothing to do with static analysis in PHPStan.
It is about dynamic data types defined and checked in runtime. You are welcome to use both.
 
### Why do you need generics?

To write much less code for data type checks.

Data obtained from databases, APIs, and 3rd party code may have unexpected structure.
For single-value variables we define types of parameters to ensure the data comes as expected.
But for an array or ArrayObject one cannot define types of values in it.

Generics allow defining types for collections with just one short attribute.
When we fill a generic object with values, and for some reason a null, false, '' or something else comes in, 
PHP will trigger the TypeException, same as with the singe-value parameters.
And yet you have just one Collection class for all types you need.

### Howto
1. Add the package as a dependency for Composer, as usually: `composer require grikdotnet\generics`. 
2. Add a call `new \grikdotnet\generics\Enable();` in bootstrap to enable the class loader.

3. Define a wildcard declaration. Of course, it can be accompanied by the PHPDoc tags for static analysis.

```php
/**
 * @template T
 */
#[\Generics\T]
class Collection extends \ArrayObject{
    use \grikdotnet\generics\GenericTrait;

    /**
     * @param $key
     * @param T $value
     * @return void
     */
    public function offsetSet(#[\Generics\T] $key, #[\Generics\T] $value )
    {
        parent::offsetSet($key,$value);
    }
}
```

Using trait is optional. It provides a convenient method T() to create concrete types:

```php
/** @var Collection $collection */
$collection = new (Collection::T("int","float"))();
$collection[] = 0.5;
```
That's it. Now the ArrayObject has types defined for the values.
Passing any data that is not a float will tribber the TypeError exception.

Here we define the type of parameter using a typed collection:

```php
class Model{
    /**
    * @param Collection $numeric
    * @return int
    */
    public function multiply(#[\Generics\T("Collection<int><float>")] $numeric): int 
    {
        $result = 0;
        for ($numeric as $key => $value) {
            $value *= 2;
        }
        return $result;
    }
}
```

Now we can avoid writing a lot of type checks in a loop over data sets in every method.

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
 

### Implemented
* Parsing PHP code that contains generics in an autoloader.
* Generation of a virtual class for a concrete type.
* Altering the source code of the loaded classes. A concrete type defined in the attribute
for methods parameters is added to parameter declaration.
* A stream wrapper to load virtual classes with include() that uses opcache.
* The TypeError exception with the correct error message and a backtrace.
* Caching the generated concrete classes in opcache, as well as results of parsing the template classes, avoiding overhead in runtime.


### Can be implemented
* Generic union types 
* Generic types for property hooks in PHP 8.4
* Generic return types
