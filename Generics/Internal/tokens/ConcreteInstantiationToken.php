<?php declare(strict_types=1);

namespace Generics\Internal\tokens;

/**
 * @internal
 */
class ConcreteInstantiationToken extends Token
{
    public function __construct(
        public readonly int    $offset,
        public readonly int    $length,
        public readonly string $type, // a class being instantiated
        public readonly array $concrete_types // a concrete type to replace the template with
    ) {}
}