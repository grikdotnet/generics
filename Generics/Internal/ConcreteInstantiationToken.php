<?php declare(strict_types=1);

namespace Generics\Internal;

/**
 * @internal
 */
readonly class ConcreteInstantiationToken
{
    public function __construct(
        public string  $class_name, // a class being instantiated
        public int     $offset,
        public ?string $concrete_type // a concrete type to replace the template with
    ) {}
}