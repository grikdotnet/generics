## Generic class declaration and instantiation

To declare a  wildcard class use the #[\Generics\T] attribute for a class and parameters.
There are two ways to declare wildcard classes and create instances with concrete types.

### Without a traint

```php
#[\Generics\T]
class Foo
{
    public function __construct(#[\Generics\T] $param)
    {
    }
}
```

To create an instance with a concrete type, use an arrow function,
and define the concrete type in a parameter of the attribute:
```php
$element = new \ACME\Bar;
$object = (#[Generics\T(\ACME\Bar)] fn($x) => new Foo($x))($element);
```
In the version 1.0 of this package, only one wildcard parameter can be set with the arrow function syntax. 

### With a traint

The second syntax uses a trait with a shortcut method.
This way one can define multiple wildcard parameters.
```php
#[\Generics\T]
class Collection
{
    use \grikdotnet\generics\GenericTrait;
    public function __construct(#[\Generics\T] $x, #[\Generics\T] $y)
    {
    }
}
```

To create a concrete instance use the `T()` method and define types in the attribute: 
```php
$element = new \ACME\Foo();
$collection = new (\samples\Collection::T('int',\ACME\Foo::class))(42,$element);
```

The wildcard class and methods with wildcard parameters must not be final
because concrete classes extend the wildcard one.

## Declaring concrete types for method parameters 

You can define a concrete type for a method parameter.

In the first syntax we define the full type as a parameter of the attribute, 
and no type for the method parameter:
```php
class Model
{
    public function process(#[\Generics\T("\samples\Collection<\samples\Rate>")] $collection)
    {
    }
}
```

In the second syntax we define the wildcard class name as a parameter type, 
and the concrete types as the attribute parameters:
```php
class Model
{
    public function process(#[\Generics\T(samples\Rate::class] \samples\Collection $collection)
    {
    }
}
```

## Namespaces
When a user class or interface is used to define a concrete type in the attribute, 
the Fully-Qualified Class Name must be used:

```php
namespace ACME;

class Model
{
    public function process(#[\Generics\T(\ACME\Foo::class] \samples\Collection $collection)
    {
    }
}
```

