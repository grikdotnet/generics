<?php declare(strict_types=1);

namespace Generics\Internal;

/**
 * @internal
 */
final class ClassAggregate implements \Iterator
{
    use TokenIterator;

    const FILENAME = 100;
    const TOKENS = 101;
    const IS_TEMPLATE = 102;
    const CLASSNAME = 103;
    /**
     * @var array<int,MethodAggregate>
     */
    protected array $tokens = [];

    private bool $is_template = false;

    public readonly string $classname;

    public function __construct(
        public readonly string $filename,
    ){}

    public function setClassname(string $classname): void
    {
        $this->classname = $classname;
    }

    public function addMethodAggregate(MethodAggregate $method): void
    {
        $this->tokens[$method->offset] = $method;
        $this->sorted = false;
    }

    public function setIsTemplate(): void
    {
        $this->is_template = true;
    }

    /**
     * Sort the tokens by the reverse position in a file
     * @return void
     */
    public function sort(): void
    {
        if (!$this->sorted) {
            krsort($this->tokens, \SORT_NUMERIC);
            $this->sorted = true;
        }
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
            self::FILENAME => $this->filename,
            self::IS_TEMPLATE => $this->is_template,
            self::CLASSNAME => $this->classname,
        ];
        $array[self::TOKENS] = [];
        foreach ($this->tokens as $t){
            $array[self::TOKENS][$t->offset] = $t->toArray();
        }
        return $array;
    }

    static public function fromArray(array $data): self
    {
        $instance = new self($data[self::FILENAME]);
        $instance->is_template = $data[self::IS_TEMPLATE];
        $instance->classname = $data[self::CLASSNAME];
        foreach ($data[self::TOKENS] as $k => $t) {
            $instance->tokens[$k] = MethodAggregate::fromArray($t);
        }
        return $instance;
    }
}
