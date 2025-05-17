<?php declare(strict_types=1);

namespace grikdotnet\generics\Internal\tokens;

/**
 * @internal
 */
final class ClassAggregate
{
    private const TOKENS = 101;
    private const IS_TEMPLATE = 102;
    private const CLASSNAME = 103;
    /**
     * @var array<int,MethodHeaderAggregate>
     */
    protected array $tokens = [];

    private bool $is_template = false;

    public function __construct(
        public readonly string $classname,
        public readonly string $namespace = ''
    ){}

    static public function fromArray(array $data): self
    {
        $instance = new self($data[self::CLASSNAME]);
        $instance->is_template = $data[self::IS_TEMPLATE];
        foreach ($data[self::TOKENS] as $k => $t) {
            $instance->tokens[$k] = MethodHeaderAggregate::fromArray($t);
        }
        return $instance;
    }

    public function addMethodAggregate(MethodHeaderAggregate $method): void
    {
        $this->tokens[$method->offset] = $method;
    }

    public function setIsTemplate(): void
    {
        $this->is_template = true;
    }

    /**
     * Check if the class contains a template type
     * @return bool
     */
    public function isTemplate(): bool
    {
        return $this->is_template;
    }

    /**
     * Check if the class contains template or generic parameters
     * @return bool
     */
    public function hasGenerics(): bool
    {
        return $this->is_template || $this->tokens !== [];
    }

    public function toArray(): array
    {
        $array = [
            self::IS_TEMPLATE => $this->is_template,
            self::CLASSNAME => $this->classname,
        ];
        $array[self::TOKENS] = [];
        foreach ($this->tokens as $t){
            $array[self::TOKENS][$t->offset] = $t->toArray();
        }
        return $array;
    }

    /**
     * @return array[int,MethodHeaderAggregate]
     */
    public function getTokens(): array
    {
        return $this->tokens;
    }

    public function getFQCN(): string
    {
        return ('' === $this->namespace) ? $this->classname : ($this->namespace.'\\'.$this->classname);
    }
}
