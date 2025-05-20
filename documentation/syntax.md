## Generic class declaration and instantiation

This package provides generic programming with user classes.
First, we declare a wildcard class. When we create an instance, we define its concrete type.
We may define a concrete type for a method parameter.

To declare a wildcard class, use the #[\Generics\T] attribute for a class and parameters.

There are two ways to declare wildcard classes and create instances with concrete types.

### With a traint

The package provides a trait with a shortcut method T():

```php
#[\Generics\T]
class Collection extends \ArrayObject
{
    use \grikdotnet\generics\GenericTrait;
    public function offsetSet(#[\Generics\T] $key, #[\Generics\T] $value): void
    {
        parent::offsetSet($key, $value);
    }
}
```

To create an instance of a concrete type, use the `T()` method:
```php
$collection = new (\samples\Collection::T('int',\Foo::class));
$element = new \Foo();
$collection[] = $element;
```

### Without a traint

The trait is optional.

```php
#[\Generics\T]
class Foo
{
    public function __construct(#[\Generics\T] $param)
    {
    }
}
```

To create an instance of a wildcard class without a trait, use an arrow function,
and define the concrete type in a parameter of the attribute:
```php
$element = new \ACME\Bar;
$object = (#[Generics\T(\ACME\Bar)] fn($x) => new Foo($x))($element);
```
In the version 1.0 of this package, only one wildcard parameter can be wildcard in the arrow function syntax. 

The syntax with an arrow function can be used to instantiate a class with a trait as well. 

The wildcard class and methods with wildcard parameters MUST NOT be final
because concrete classes extend the wildcard one.

## Declaring concrete types of method parameters 

You can define a concrete type for a method parameter.
Again, there are two possible syntaxes.

With the first syntax, define the type as a parameter of the attribute in the
"wildcard<concrete>" format. If the method parameter is defined, it MUST match the wildcard class name 
defined in the attribute parameter.
```php
namespace ACME;
class Model
{
    public function process(#[\Generics\T("\ACME\Collection<int><\My\Foo>")] Collection $collection)
    {
    }
}
```

With the second syntax use the wildcard class name as a parameter type, 
and provide the concrete type as the attribute parameters:
```php
class Model
{
    public function process(#[\Generics\T("int", \ACME\Foo::class)] \samples\Collection $collection)
    {
    }
}
```


## Concrete types

Those PHP data types that can be used as a type of a function parameter, can be 
passed to the #[\Generics\T("type") attribute.

* Class and interface names, both user and built-in, enums.

To define a concrete type in the attribute, always use the Fully-Qualified Class Name format.
When a non-FQCN name is used within a namespace, the actual type may be unpredictable.

```php
namespace ACME;

class Model
{
    public function process(#[\Generics\T("\ACME\Collection<\ACME\Item>")] Collection $collection)
    {
    }
}
```


* Built-in types, such as int, string, etc. 

A type that is a reserved word, such as int, should be provided in quotes.
 
* Union and intersection types are not supported.
* Usage of relative class types "self", "parent", and "static" may cause unpredicable behavior.
