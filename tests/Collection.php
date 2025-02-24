<?php

namespace tests;

use ACME\WithoutErrorHandler;

#[Generics\T]
class Collection {
    private array $elements = [];

    public function add(#[Generics\T] $element, $key = null): void
    {
        $this->elements[] = $element;
    }

    public function get($key): mixed
    {
        return $this->elements[$key] ?? null;
    }
}