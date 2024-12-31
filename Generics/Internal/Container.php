<?php declare(strict_types=1);

namespace Generics\Internal;

/**
 * @internal
 */
class Container
{
    public array $files_classes = [];
    /**
     * @var array<ParameterTokenAggregate> $class_tokens
     */
    public array $class_tokens = [];
    public array $instantiations = [];
    public FileReader $reader;

    public function __construct() {
        $this->reader = new FileReader;
    }

    /**
     * @param string $class_name
     * @return bool
     */
    public function isClassTemplate(string $class_name): bool
    {
        return isset($this->class_tokens[$class_name])
            && $this->class_tokens[$class_name] instanceof ParameterTokenAggregate
            && $this->class_tokens[$class_name]->isTemplate()
        ;
    }

    /**
     * @param string $class_name
     * @return ParameterTokenAggregate|null
     */
    public function getClassTokens(string $class_name): ?ParameterTokenAggregate
    {
        return $this->class_tokens[$class_name] ?? null;
    }

    public function addClassTokens(string $filename, string $class_name, ParameterTokenAggregate $aggregate): void
    {
        $this->files_classes[$filename][] = $class_name;
        $this->class_tokens[$class_name] = $aggregate;
    }

    public function addNewInstanceTokens(string $filename, NewInstanceTokenAggregate $newInstanceAggregate): void
    {
        $this->instantiations[$filename] = $newInstanceAggregate;
    }


}