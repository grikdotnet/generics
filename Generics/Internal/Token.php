<?php declare(strict_types=1);

namespace Generics\Internal;

/**
 * @internal
 */
readonly class Token
{
    public TypeType $type_type;
    public function __construct(
        public int      $offset,
        public string   $parameter_name,
        public ?string   $parameter_type,
        TypeType $type_type = TypeType::Atomic
    ) {
        $this->type_type = $type_type;
    }
}