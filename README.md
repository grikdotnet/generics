# Generics in PHP

* [Story of generics in PHP](documentation/story.md)
* [Why do you need generics programming](#why-do-you-need-generics)
* [How to use this package](#how-to-use)
* [Syntax](documentation/syntax.md)
* [System requirements and compatibility](documentation/compatibility.md)
* [Implementation details](documentation/implementation.md)
 
### Why do you need generics?
[Generic programming](https://en.wikipedia.org/wiki/Generic_programming) is an algorithm where data types are declared as "to-be-specified-later", when needed.

It allows writing much less code, and have data types checked by the PHP engine in data sets.

Data may have unexpected structure, especially when it is obtained from databases, APIs, and 3rd party code.
For single-value variables we define parameter types, but for the composite types such as array, ArrayObject,
SplFixedArray one cannot define types of values in runtime.
To define data types for values we could create multiple classes with the same code, 
where the only difference would be a type of a parameter. 
E.g.
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
```
This feels wrong, and violates the "Don't repeat yourself" principle.

Generics allow defining types of parameters when you create an instance, with just one short clause.
And yet you have just one class declaration for all types you need.

### How to use
1. Add the package as a dependency for Composer, as usually: `composer require grikdotnet\generics`. 
2. Call `new \grikdotnet\generics\Enable();` in bootstrap to enable the class loader.
3. Declare a wildcard class.

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

Now let's use the typed Collection as a parameter type in a method:

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

This way data types of elements are checked by the PHP engine, and
we can avoid writing a lot of type checks in a loop over data sets in every method.

The syntax is fully compatible with PHP.
If the generics are not enabled, the code will run without concrete types.

Find more about syntax in the [documentation](documentation/syntax.md).