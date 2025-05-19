<?php declare(strict_types=1);

namespace grikdotnet\generics\Internal\tokens;

/**
 * @internal
 */
class ConcreteInstantiationToken extends Token
{
    public function __construct(
        public readonly int    $offset,
        public readonly int    $length,
        public readonly string $type, // a class being instantiated
        public readonly array $concrete_types, // a concrete type to replace the template with
        public readonly string $concrete_name // a concrete type to replace the template with
    ) {}

    public function toArray(): array
    {
        return [$this->offset,$this->length,$this->type,$this->concrete_types,$this->concrete_name];
    }
}