<?php declare(strict_types=1);

namespace Generics\Internal;

/**
 * @internal
 */
final class ParameterTokenAggregate extends TokenAggregate
{
    private bool $is_template = false;

    public function __construct(
        public readonly string $filename,
        public readonly string $classname
    ){}

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
}
