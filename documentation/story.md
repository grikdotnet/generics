### The story of generics in PHP

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

### Why generics became possible in PHP now

2. Early implementations were slow.
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

* Parsing PHP code with generic attributes.
* Generation of a virtual class for a concrete type.
* Altering the source code of the loaded classes. A concrete type defined in the attribute
  for methods parameters is added to parameter declaration.
* A stream wrapper to load virtual classes with include() that uses opcache.
* The TypeError exception with the correct error message and a backtrace.
* Caching the generated concrete classes in opcache, as well as results of parsing the template classes, avoiding overhead in runtime.

### Can be implemented
* 
* Wildcard return types
* Wildcard union and intersection types
* Generic types for property hooks in PHP 8.4
