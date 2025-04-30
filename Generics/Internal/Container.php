<?php declare(strict_types=1);

namespace Generics\Internal;

use Generics\Internal\tokens\ClassAggregate;
use Generics\Internal\tokens\FileAggregate;

/**
 * @internal
 */
class Container
{
    public static self $instance;
    /**
     * @var array<string,FileAggregate> $fileAggregates
     */
    public array $fileAggregates = [];
    /**
     * @var array<class-string,ClassAggregate> $classAggregates
     */
    public array $classAggregates = [];
    /**
     * Contents of files with code to parse for tokens
     * @var array<non-empty-string,string>
     */
    public array $files = [];
    /**
     * A virtual file is an augmented code from a real file with concrete types from attributes
     * @var array<string,VirtualFile> $vfiles
     */
    public array $vfiles = [];
    /**
     *  A virtual class is a class with concrete types extending a template class
     *  @var array<class-string,VirtualFile> $virtual_classes
     */
    public array $vclasses = [];
    /**
     * @var array<string>
     */
    public array $skip_files = [];

    /**
     * Populated from OpCache, contains dehydrated tokens as numeric arrays
     * @var array
     */
    private array $classes_tokens_cache = [];

    /**
     * Populated from OpCache, contains dehydrated tokens as numeric arrays
     * @var array
     */
    private array $files_tokens_cache = [];

    /**
     * A boolean mask marking which items were added to store in Opcache
     * @var int
     */
    private int $modified = 0;

    /**
     * A singleton implementation
     * @return self
     */
    public static function getInstance(): self
    {
        return self::$instance ?? self::$instance = new self();
    }
    private function __construct()
    {}

    /**
     * @param class-string $class_name
     * @return bool
     */
    public function isClassTemplate(string $class_name): bool
    {
        return ($classTokens = $this->getClassTokens($class_name))
            && $classTokens->isTemplate()
        ;
    }

    /**
     * @param class-string $class_name
     * @return ClassAggregate|null
     */
    public function getClassTokens(string $class_name): ?ClassAggregate
    {
        if (isset($this->classAggregates[$class_name])) {
            return $this->classAggregates[$class_name];
        }
        if (isset($this->classes_tokens_cache[$class_name])) {
            return $this->classAggregates[$class_name] = ClassAggregate::fromArray($this->classes_tokens_cache[$class_name]);
        }
        return null;
    }

    /**
     * @param string $path
     * @return FileAggregate|null
     */
    public function getFileTokens(string $path): ?FileAggregate
    {
        if (isset($this->fileAggregates[$path])) {
            return $this->fileAggregates[$path];
        }
        if (isset($this->files_tokens_cache[$path])) {
            return $this->fileAggregates[$path] = FileAggregate::fromArray($this->files_tokens_cache[$path]);
        }
        return null;
    }

    /**
     * @param FileAggregate $fileAggregate
     * @return void
     */
    public function addFileTokens(FileAggregate $fileAggregate): void
    {
        $this->fileAggregates[$fileAggregate->path] = $fileAggregate;
        foreach ($fileAggregate->classAggregates as $c) {
            $this->classAggregates[$c->classname] = $c;
        }
        $this->modified |= 1;
    }

    /**
     * @param string $path
     * @return void
     */
    public function addToSkipFiles(string $path): void
    {
        $this->skip_files[] = $path;
        $this->modified |= 1;
    }

    /**
     * @param VirtualFile $param
     * @return void
     */
    public function addAugmentedFile(VirtualFile $param)
    {
        $this->vfiles[$param->path] = $param;
    }

    /**
     * @param non-empty-string $filename
     * @return VirtualFile | null
     */
    public function getVirtualFile(string $filename): ?VirtualFile
    {
        if (isset($this->vfiles[$filename])) {
            return $this->vfiles[$filename];
        }
        return null;
    }

    public function isModified(): bool
    {
        return $this->modified !== 0;
    }
    public function areNewTokens(): bool
    {
        return (bool)($this->modified & 1);
    }

    /**
     * Create a reference to opcache during initialization
     *
     * @param array $file_tokens
     * @param array $class_tokens
     * @param array $skip_files
     * @return void
     */
    public function setCache(array $file_tokens, array $class_tokens, array $skip_files): void
    {
        $this->files_tokens_cache = $file_tokens;
        $this->classes_tokens_cache = $class_tokens;
        $this->skip_files = $skip_files;
    }
}
