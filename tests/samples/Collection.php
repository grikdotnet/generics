<?php

namespace samples;

/**
 * @template T
 */
#[\Generics\T]
class Collection extends \ArrayObject
{
    public function offsetSet($key, #[\Generics\T] $value): void
    {}
}
