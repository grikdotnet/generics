<?php declare(strict_types=1);

namespace Generics\Internal;

/**
 * @internal
 */
final class ConcreteInstantiationAggregate implements \Iterator
{
    use TokenIterator;

    /**
     * @var array<ConcreteInstantiationToken>
     */
    protected array $tokens = [];

    public function __construct(
        public readonly string $filename
    ){}

    public function addToken(ConcreteInstantiationToken $token): void
    {
        $this->tokens[$token->offset] = $token;
        $this->sorted = false;
    }

    public function hasTokens(): bool
    {
        return $this->tokens !== [];
    }

}