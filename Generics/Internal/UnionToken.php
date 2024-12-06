<?php declare(strict_types=1);

namespace Generics\Internal;

/**
 * @internal
 */
readonly class UnionToken extends Token
{
    public TypeType $type_type;
    public function __construct(
        public int      $offset,
        public string   $parameter_name,
        public array    $union_types
    ) {
        $this->type_type = TypeType::Union;
    }
}