<?php

namespace samples;

/**
 * @template T
 */
#[\Generics\T]
class Collection2 extends \ArrayObject
{
    use \grikdotnet\generics\GenericTrait;

    /**
     * @param $key
     * @param $value
     * @return Void
     */
    public function offsetSet(#[\Generics\T] $key, #[\Generics\T] $value): Void
    {
        parent::offsetSet($key, $value);
    }
}
