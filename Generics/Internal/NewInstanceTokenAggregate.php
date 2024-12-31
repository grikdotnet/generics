<?php declare(strict_types=1);

namespace Generics\Internal;

final class NewInstanceTokenAggregate extends TokenAggregate
{
    protected array $tokens = [];

    public function __construct(
        public readonly string $filename
    ){}

    public function hasInstantiation(): bool
    {
        return (bool)$this->tokens;
    }
    public function addToken(Token $token): void
    {
        $this->tokens[$token->offset] = $token;
        $this->sorted = false;
    }

    public function hasTokens(): bool
    {
        return $this->tokens !== [];
    }

}