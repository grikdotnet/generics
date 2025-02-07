<?php declare(strict_types=1);

namespace Generics\Internal;

/**
 * @internal
 */
trait TokenIterator {

    protected bool $sorted = false;
    /**
     * @return array<WildcardParameterToken|ConcreteInstantiationToken>
     */
    public function getTokens(): array
    {
        $this->sorted || $this->sort();
        return $this->tokens;
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

    public function current(): WildcardParameterToken | ConcreteInstantiationToken | MethodAggregate
    {
        $this->sorted || static::sort();
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