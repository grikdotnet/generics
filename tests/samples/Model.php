<?php declare(strict_types=1);

namespace samples;

class Model
{
    public function process(#[\Generics\T("\samples\Collection<\samples\Rate>")] $collection)
    {
    }
}