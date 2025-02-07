<?php declare(strict_types=1);

namespace Generics\Internal;

/**
 * @internal
 */
final class ClassAggregate implements \Iterator
{
    use TokenIterator;
    /**
     * @var array<MethodAggregate> $methods
     */
    protected array $tokens = [];

    private bool $is_template = false;

    public function __construct(
        public readonly string $filename,
        public readonly string $classname
    ){
    }

    public function addMethodAggregate(MethodAggregate $method): void
    {
        $this->tokens[$method->offset] = $method;
        $this->sorted = false;
    }

    /**
     * @TODO implement wildcard property tokens
     */
    public function addWildcardPropertyToken(WildcardPropertyToken $token): void
    {
        $this->tokens[$token->offset] = $token;
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
            foreach ($this->tokens as $token) {
                $token instanceof Token && $token->sort();
            }
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
}
