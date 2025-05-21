## System requirements, compatibility, and side effects

The package works with PHP versions 8.2 - 8.4.

The package relies on the Composer to be used as a class loader. 
The functionality must be enabled by executing `new \grikdotnet\generics\Enable();` 
before loading code with generics.
The code loaded with include(), require(), and eval() is not supported.
In the code loaded directly or before enabling the package the `#[\Generics\T]` attributes are ignored.

The package is optimized to use Opcache extension, but works without it.
Set a larger than default values cache TTL to avoid parsing of the generics declarations, e.g.
`opcache.revalidate_freq=600`, or `opcache.validate_timestamps=0` to disable invalidation of cached code.

If PHP does not have the phar extension, the "phar://" stream wrapper is registered.
If the phar extension is loaded, the "phar://" stream wrapper is temporarily re-registered to save a concrete 
class declaration to Opcache, and reset to the system default.

[Next Chapter: Solution diagrams](implementation.md)