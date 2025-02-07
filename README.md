# Generics in PHP
A partial implementation of the generic programming in PHP.


Requirements and dependencies: see [composer.json](https://github.com/grikdotnet/generics/blob/master/composer.json)

### Syntax
Wildcard and concrete types are defined with attributes. 
It is enabled with a call `new \Generics\Enable();`

A wildcard template declaration:
```php
#[\Generics\T]
class Foo{
    public function __construct(
        int $x, #[\Generics\T] $param
    ){}
}
```
It works with non-final classes only.

Instantiation with a concrete type should be wrapped in an arrow function:
```php
$foo = (#[Generics\T("float")] fn() => new \Foo(1, $y))();
```
As for now, the type must be defined in parentheses as "float".

### How it will work
1. When enabled, an autoloader is added and a "generic" file wrapper is registered. 
2. When a new class is instantiated, an autoload takes a path from the Composer, and reads the source. 
3. The code with generics is parsed, creating tokens for wildcard and concrete parameters, and type instantiations.
 is a test for generation of tokens. The tokens for a file can be cached. 
For details please check the [TemplateDeclarationTest](https://github.com/grikdotnet/generics/blob/master/tests/TemplateDeclarationTest.php). 
4. When parser meets an instantiation clause in an arrow function it replaces 
a class name with a name of a concrete virtual class.
6. The concrete virtual class is registered in the Composer with a wrapper in the path.
7. `include()` cals a wrapper. 
8. A wrapper generates a code of the concrete virtual class that inherits the template class
using tokens from a parser, and a concrete type defined in the instantiation attribute.
Check the [ConcreteInstanceGenerationTest](https://github.com/grikdotnet/generics/blob/master/tests/ConcreteInstanceGenerationTest.php)
for details. 
8. Wrapper returns the generated code of a generated concrete virtual class. This code is cached in a bytecode cache.
9. PHP creates an instance of a concrete virtual class instead.