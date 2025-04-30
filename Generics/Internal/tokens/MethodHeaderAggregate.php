<?php declare(strict_types=1);

namespace Generics\Internal\tokens;

class MethodHeaderAggregate {
    const OFFSET = 10;
    const LENGTH = 11;
    const NAME = 12;
    const PARAMETERS = 13;
    const WILDCARD = 14;
    const HEADLINE = 15;
    /**
     * @var array<Parameter>
     */
    public array $parameters = [];
    public bool $wildcardReturn = false;

    /**
     * @param int $offset
     * @param int $length from the start of a method declaration till the opening {
     * @param string $name
     */
    public function __construct(
        public readonly int $offset,
        public readonly int $length,
        public readonly string $name,
        public string $headline
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
        $p = [];
        foreach ($this->parameters as $param) {
            $p[] = array_values((array)$param);
        }
        return [
            self::OFFSET => $this->offset,
            self::LENGTH => $this->length,
            self::NAME => $this->name,
            self::WILDCARD => $this->wildcardReturn,
            self::HEADLINE => $this->headline,
            self::PARAMETERS => $p,
        ];
    }

    static public function fromArray(array $data): self
    {
        $instance = new self($data[self::OFFSET],$data[self::LENGTH],$data[self::NAME],$data[self::HEADLINE]);
        foreach ($data[self::PARAMETERS] as $p) {
            $instance->parameters[] = new Parameter(...$p);
        }
        $instance->wildcardReturn = $data[self::WILDCARD];
        return $instance;
    }
}