<?php declare(strict_types=1);

namespace Generics\Internal;

/**
 * @internal
 */
class Container
{
    /**
     * @var array<TokenAggregate> $classes
     */
    public array $classes = [];
    public FileCache $cache;

    public function __construct() {
        $this->cache = new FileCache;
    }

    /**
     * @param string $class_name
     * @return bool
     */
    public function isClassTemplate(string $class_name): bool
    {
        return isset($this->classes[$class_name])
            && $this->classes[$class_name] instanceof TokenAggregate
            && $this->classes[$class_name]->isTemplate()
        ;
    }

    /**
     * @param string $class_name
     * @return TokenAggregate|null
     */
    public function getClassTokens(string $class_name): ?TokenAggregate
    {
        return $this->classes[$class_name] ?? null;
    }
}