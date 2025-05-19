<?php declare(strict_types=1);

namespace samples;

class Model
{
    public function syntax1(#[\Generics\T("\samples\Collection<\samples\Rate>")] $collection)
    {
    }
    public function syntax2(#[\Generics\T(\samples\Rate::class)] Collection $collection)
    {
    }
    public function multiparam1(#[\Generics\T('Collection2<int><float>')] $collection)
    {
    }
    public function multiparam2(#[\Generics\T('int','float')] Collection2 $collection)
    {
    }
}