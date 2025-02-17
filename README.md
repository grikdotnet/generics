# Generics in PHP
A partial implementation of the generic programming in PHP.

### Syntax
Wildcard and concrete types are defined with attributes. 
It is enabled with a call `new \Generics\Enable();`

A wildcard template declaration example:
```php
#[\Generics\T]
class Foo{
    use \Generics\GenericTrait;
    public function __construct(
        int $x, #[\Generics\T] public $param
    ){}
}
```

Instantiation with a concrete type:
```php
$foo = Foo::new("int")(42);
```
Check [InstantiationTraitTest.php](tests/InstantiationTraitTest.php) for the demo code.

Define a concrete type as a parameter:
```php
class Bar{
    public function bar(#[\Generics\T("Foo<int>")] $param){}
}
```

### How it works
1. When enabled, an autoloader and a "generic" file wrapper are registered. 
2. When a new class with a trait is instantiated, a loader parses the source code and generates a virtual class for 
a concrete type. The virtual concrete class extends the wildcard class.
Please check the [TemplateDeclarationTest](https://github.com/grikdotnet/generics/blob/master/tests/TemplateDeclarationTest.php) for details of parsing files. 
3. `include()` cals a wrapper that serves the generated code for the virtual class.
4. PHP creates an instance of a concrete virtual class and a Closure that calls a constructor with parameters provided.

### Can be implemented
* Avoid overhead completely by caching the generated concrete classes declaration in opcache, as well as results 
of parsing of the template classes.
* Generic union types 
* Generic hook types in PHP 8.4

### Could not find a solution for:
* Autocompletion for the constructor parameters in IDEs when instantiating concrete types