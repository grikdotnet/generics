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
     */
    public function __construct(
        public readonly int $offset,
        public readonly int $length,
        public readonly string $name
    ){}

    /**
     * @param Parameter $parameter
     * @return void
     */
    public function addParameter(Parameter $parameter): void
    {
        $this->parameters[] = $parameter;
    }

    /**
     * @return void
     */
    public function setWildcardReturn(): void
    {
        $this->wildcardReturn = true;
    }

    /**
     * @return bool
     */
    public function isGeneric(): bool
    {
        return $this->wildcardReturn || array_reduce($this->parameters, fn($c, $p) => $c || $p->is_wildcard || $p->is_concrete_type);
    }
}