<?php declare(strict_types=1);

namespace Generics\Internal;

class Container
{
    /**
     * @var array<ClassAggregate> $classes
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
            && $this->classes[$class_name] instanceof ClassAggregate
            && $this->classes[$class_name]->isTemplate()
        ;
    }

    /**
     * @param string $class_name
     * @return ClassAggregate|null
     */
    public function getClassTokens(string $class_name): ?ClassAggregate
    {
        return $this->classes[$class_name] ?? null;
    }
}