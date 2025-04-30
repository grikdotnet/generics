<?php declare(strict_types=1);

namespace Generics\Internal\tokens;

class Parameter extends Token{
    public function __construct(
        public readonly int    $offset,
        public readonly int    $length,
        public readonly string $name,
        public readonly string $type = '',
        public readonly bool   $is_wildcard = false,
        public readonly string $concrete_type = '',
    )
    {}
}