<?php declare(strict_types=1);

namespace samples;

/**
 * @template T
 */
#[\Generics\T]
class Collection extends \ArrayObject
{
    use \grikdotnet\generics\GenericTrait;

    /**
     * @param $key
     * @param $value
     * @return Void
     */
    public function offsetSet($key, #[\Generics\T] $value): Void
    {
        parent::offsetSet($key, $value);
    }
}
