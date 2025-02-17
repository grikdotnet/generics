# Generics in PHP
A partial implementation of the generic programming in PHP. 
 
Uses native PHP type system with no overhead in runtime checks for generic types.

This is a working proof of concept without caching, please use for evaluation, not for production.

To evaluate just clone the repo, and run `composer install`.

### Syntax
First, call `new \Generics\Enable();` to initialise the package before the generic functionality is used.

To use generic types one should define a wildcard template class and instantiate it with a concrete type.
The wildcard and concrete types are defined with attributes.

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

Create an instance of a concrete type:
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