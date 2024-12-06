<?php declare(strict_types=1);

namespace Generics\Internal;

/**
 * @internal
 */
final class TokenAggregate implements \Iterator
{
    private bool $is_template = false;
    private array $tokens = [];

    private bool $sorted = false;

    public function __construct(public readonly string $classname){}

    public function setIsTemplate(): void
    {
        $this->is_template = true;
    }

    public function addToken(Token $token): void
    {
        $this->tokens[$token->offset] = $token;
        $this->sorted = false;
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

    public function getTokens(): array
    {
        $this->sorted || $this->sort();
        return $this->tokens;
    }

    /**
     * Sort the tokens by the reverse position in a file
     * @return void
     */
    private function sort(): void
    {
        if (!$this->sorted) {
            krsort($this->tokens, \SORT_NUMERIC);
            $this->sorted = true;
        }
    }

    /**
     * Return the current element
     * @return Token
     */
    public function current(): mixed
    {
        $this->sorted || $this->sort();
        return \current($this->tokens);
    }

    /**
     * Move forward to next element
     * @return void
     */
    public function next(): void
    {
        $this->sorted || $this->sort();
        \next($this->tokens);
    }

    /**
     * Return the key of the current element
     * @return int|null
     */
    public function key(): ?int
    {
        return \key($this->tokens);
    }

    /**
     * Checks if current position is valid
     * @return bool
     */
    public function valid(): bool
    {
        return \key($this->tokens) !== null;
    }

    /**
     * Rewind the Iterator to the first element
     * @return void Any returned value is ignored.
     */
    public function rewind(): void
    {
        $this->sorted || $this->sort();
        reset($this->tokens);
    }
}
