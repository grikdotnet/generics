<?php declare(strict_types=1);

namespace samples;

class ArrowFactory
{
    public function createCollection(): Collection
    {
        $c = (#[\Generics\T("int")] fn() => new \samples\Collection())();
        return $c;
    }


    public function createNoNamespace(): Collection
    {
        $c = (#[\Generics\T("NoNamespace")] fn() => new \samples\Collection())();
        return $c;
    }

}
