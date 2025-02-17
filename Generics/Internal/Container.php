<?php declare(strict_types=1);

namespace Generics\Internal;

/**
 * @internal
 */
class Container
{
    /**
     * @var array<string,string>
     */
    public array $files_classes = [];
    /**
     * @var array<ClassAggregate> $class_tokens
     */
    public array $class_tokens = [];
    /**
     * @var array<string,ConcreteInstantiationAggregate>
     */
    public array $instantiations = [];
    /**
     * @var array<string,string>
     */
    public array $files;
    /**
     * @var array<string,VirtualFile>
     */
    public array $vfiles;
    /**
     * @var array<string,VirtualFile>
     */
    public array $virtual_classes;

    /**
     * @param string $class_name
     * @return bool
     */
    public function isClassTemplate(string $class_name): bool
    {
        return isset($this->class_tokens[$class_name])
            && $this->class_tokens[$class_name] instanceof ClassAggregate
            && $this->class_tokens[$class_name]->isTemplate()
        ;
    }

    /**
     * @param string $class_name
     * @return ClassAggregate|null
     */
    public function getClassTokens(string $class_name): ?ClassAggregate
    {
        return $this->class_tokens[$class_name] ?? null;
    }

    public function addClassTokens(ClassAggregate $class): void
    {
        $this->files_classes[$class->filename][] = $class->classname;
        $this->class_tokens[$class->classname] = $class;
    }


    public function addVirtualFile(string $filename, string $content, string $reference_path): void
    {
        $this->vfiles[$filename] = new VirtualFile($filename,$content,$reference_path);
    }

    public function addVirtualClassCode(string $class, VirtualFile $vFile): void
    {
        $this->virtual_classes[$class] = $vFile;
    }

    public function findClassFile(string $class_name): string|false
    {
        return array_search($class_name,$this->files_classes,true);
    }
}