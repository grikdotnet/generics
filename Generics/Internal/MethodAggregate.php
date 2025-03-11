<?php declare(strict_types=1);

namespace Generics\Internal;

class MethodAggregate {
    const OFFSET = 10;
    const LENGTH = 11;
    const NAME = 12;
    const PARAMETERS = 13;
    const WILDCARD = 14;
    /**
     * @var array<Parameter>
     */
    public array $parameters = [];
    public bool $wildcardReturn = false;

    /**
     * @param int $offset
     * @param int $length
     * @param string $name
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

    public function toArray(): array
    {
        $p[self::OFFSET] = $this->offset;
        $p[self::LENGTH] = $this->length;
        $p[self::NAME] = $this->name;
        $p[self::WILDCARD] = $this->wildcardReturn;
        $p[self::PARAMETERS] = [];
        foreach ($this->parameters as $param) {
            $p[self::PARAMETERS][] = array_values((array)$param);
        }
        return $p;
    }

    static public function fromArray(array $data): self
    {
        $instance = new self($data[self::OFFSET],$data[self::LENGTH],$data[self::NAME]);
        foreach ($data[self::PARAMETERS] as $p) {
            $instance->parameters[] = new Parameter(...$p);
        }
        $instance->wildcardReturn = $data[self::WILDCARD];
        return $instance;
    }
}