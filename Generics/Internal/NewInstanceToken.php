<?php /** @noinspection PhpMissingParentConstructorInspection */
declare(strict_types=1);

namespace Generics\Internal;

readonly class NewInstanceToken extends Token
{
    public TypeType $type_type;
    public function __construct(
        public string   $class_name, // a class being instantiated
        public int      $offset,
        public ?string   $parameter_type // a concrete type to replace the template with
    ) {
        $this->type_type = TypeType::Instance;
    }
}