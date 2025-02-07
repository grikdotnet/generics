<?php declare(strict_types=1);

namespace Generics\Internal;

class MethodAggregate {
    /**
     * @var array<Parameter>
     */
    public array $parameters = [];
    public bool $wildcardReturn = false;

    /**
     * @param string $name
     * @param int $offset
     * @param int $length
     * @param int $parameters_offset
     */
    public function __construct(
        public readonly int $offset,
        public readonly int $length,
        public readonly string $name
    ){}

    public function addParameter(Parameter $parameter): void
    {
        $this->parameters[] = $parameter;
    }

    public function setWildcardReturn(): void
    {
        $this->wildcardReturn = true;
    }
}