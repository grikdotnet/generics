## Performance impact

A synthetical test consists of three classes loaded via Composer, 
instantiated with and without generic types,
and three calls of methods with concrete type parameters.

Tested 1000 sequential requests with `$ ab -n 1000 http://127.0.0.1:8080/`
`$ php -S "127.0.0.1:8080"` is used as a server.


|                             | Requests per second |
|-----------------------------|---------------------|
| No generics, no Opcache     | 1355 [#/sec]        |
| No generics, with Opcache   | 5531                |
| With generics, no Opcache   | 105                 |
| With generics, with Opcache | 2506                |

Opcache memory consumption without generics: 9MB.

Opcache memory consumption with generics: 11MB.

**Result**

Class instantiation is a very fast operation. 
Usage of generics is 2 times slower with Opcache.
Without Opcache the instantiation of generic classes is 10 times slower.