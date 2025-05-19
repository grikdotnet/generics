# Generics in PHP
For 15 years people were trying to implement generics in PHP.
[Anthony Ferrara](https://wiki.php.net/rfc/protocol_type_hinting),
[Elliot Levin](https://github.com/TimeToogo/PHP-Generics),
[Nikita Popov](https://github.com/PHPGenerics/php-generics-rfc/issues/45),
[Anton Sukhachev](https://github.com/mrsuh/php-generics),
and others.

In 2023 PHP Foundation funded [a year of research](https://thephp.foundation/blog/2024/08/19/state-of-generics-and-collections/) 
in generics implementation. I believe a complex perfect solution was never a PHP way, though.

PHP changed a lot over the years, and an old approach may actually work now.
This library provides a way to have generic programming in PHP. 

NB: Generic programming has nothing to do with static analysis.
Generics are about dynamic data types defined and checked in runtime. 
The static analysis helps with the code structure. You are welcome to use both.
 
### Why do you need generics?

To write much less code, and have PHP data types checked by PHP engine in data sets.

Data may have unexpected structure, especially when it is obtained from databases, APIs, and 3rd party code.
For single-value variables we define parameter types, but for the composite types such as array, ArrayObject,
SplFixedArray one cannot define types of values in runtime.
To define data types for values we can create multiple classes with the same code, 
where the only difference would be a type of a parameter. 
This feels wrong and violates the "Don't repeat yourself" principle.

```php
class CollectionInt extends \ArrayObject{
    public function offsetSet(int $key, int $value )
    {
        parent::offsetSet($key,$value);
    }
}
class CollectionFoo extends \ArrayObject{
    public function offsetSet(int $key, \Foo $value )
    {
        parent::offsetSet($key,$value);
    }
}
...
```

Generics allow defining types of parameters when you create an instance, with just one short clause.
And yet you have just one class declaration for all types you need.

### Howto
1. Add the package as a dependency for Composer, as usually: `composer require grikdotnet\generics`. 
2. Call `new \grikdotnet\generics\Enable();` in bootstrap to enable the class loader.
3. Define a wildcard declaration. Of course, it can be accompanied by the PHPDoc tags for static analysis.

```php
#[\Generics\T]
class Collection extends \ArrayObject{
    use \grikdotnet\generics\GenericTrait;

    public function offsetSet(#[\Generics\T] $key, #[\Generics\T] $value )
    {
        parent::offsetSet($key,$value);
    }
}
```

Using the trait is optional. It provides a convenient shortcut method `T()` to create concrete types:

```php
/** @var Collection $collection */
$collection = new (Collection::T("int","float"))();
$collection[] = 0.5;
```
That's it. Now PHP will check the type of values added to the ArrayObject instance, and trigger a TypeError 
when the type does not match.

Lt's use the typed Collection as a type of a method parameter:

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
What changed: we have Opcache now, and lots of memory in servers.
We can use it to avoid performance penalty.


2. PHP does not allow symbols < and > as a part of class names, so the code `MyClass<Foo>` becomes
incompatible with PHP syntax.
<br><br>
What changed: we've got Attributes. 
[PHP manual](https://www.php.net/manual/en/language.attributes.overview.php):
> With attributes the generic implementation of a feature and its concrete use in an application can be decoupled.


3. PHPStan and Psalm provide static analysis for generic types defined in comments.
They help a lot, but they don't ensure type safety in runtime for the unexpected data.
<br><br>
What changed: PHP is a compiled language with strict types now. We can have type safety for arbitrary
real-life data sets within an application itself, using native PHP functionality.
This is different from validation of the code architecture with a stand-alone static analyser.


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
