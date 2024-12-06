<?php declare(strict_types=1);

namespace Generics\Internal;

final readonly class Token
{
    public function __construct(
        public TypeType $type_type,
        public int      $offset,
        public string   $parameter_name,
        public ?string   $parameter_type
    ) {}
}