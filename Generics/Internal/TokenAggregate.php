<?php declare(strict_types=1);

namespace Generics\Internal;

abstract class TokenAggregate implements \Iterator{
    /**
     * @var array<Token|NewInstanceToken>
     */
    protected array $tokens = [];

    protected bool $sorted = false;

    public function addToken(Token $token): void
    {
        $this->tokens[$token->offset] = $token;
        $this->sorted = false;
    }

    /**
     * @return array<Token|NewInstanceToken>
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
    private function sort(): void
    {
        if (!$this->sorted) {
            krsort($this->tokens, \SORT_NUMERIC);
            $this->sorted = true;
        }
    }

    /**
     * Return the current element
     * @return Token | NewInstanceToken
     */
    public function current(): Token | NewInstanceToken
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