<?php declare(strict_types=1);

namespace Generics\Internal;

/**
 * @internal
 */
class ConcreteInstantiationToken extends Token
{
    public function __construct(
        public readonly int     $offset,
        public readonly int     $length,
        public readonly string  $class_name, // a class being instantiated
        public readonly string  $concrete_type // a concrete type to replace the template with
    ) {}
}