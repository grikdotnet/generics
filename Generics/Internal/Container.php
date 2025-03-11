<?php declare(strict_types=1);

namespace Generics\Internal;

/**
 * @internal
 */
class Container
{
    const CLASS_TOKENS = 100;
    const VFILES = 200;
    const VCLASSES = 300;
    /**
     * @var array<string,ClassAggregate> $class_tokens
     */
    public array $class_tokens = [];
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

    private array $cache = [self::CLASS_TOKENS=>[],self::VFILES=>[],self::VCLASSES=>[]];

    private bool $is_modified = false;

    public function __construct()
    {
        if (Opcache::isAvailable()){
            if ([] !== ($cache = Opcache::read()) ) {
                $this->cache = $cache;
            }
            register_shutdown_function($this->saveToCache(...));
        }
    }

    /**
     * @param string $class_name
     * @return bool
     */
    public function isClassTemplate(string $class_name): bool
    {
        return ($classTokens = $this->getClassTokens($class_name))
            && $classTokens instanceof ClassAggregate
            && $classTokens->isTemplate()
        ;
    }

    /**
     * @param string $class_name
     * @return ClassAggregate|null
     */
    public function getClassTokens(string $class_name): ?ClassAggregate
    {
        if (isset($this->class_tokens[$class_name])) {
            return $this->class_tokens[$class_name];
        }
        if (isset($this->cache[self::CLASS_TOKENS][$class_name])) {
            return $this->class_tokens[$class_name] = ClassAggregate::fromArray($this->cache[self::CLASS_TOKENS][$class_name]);
        }
        return null;
    }

    public function addClassTokens(ClassAggregate $class): void
    {
        $this->class_tokens[$class->classname] = $class;
        $this->is_modified = true;
    }

    public function addVirtualFile(string $filename, string $content, string $reference_path): void
    {
        $this->vfiles[$filename] = new VirtualFile($filename,$content,$reference_path);
        $this->is_modified = true;
    }

    public function addVirtualClassCode(string $class, VirtualFile $vFile): void
    {
        $this->virtual_classes[$class] = $vFile;
        $this->is_modified = true;
    }

    /**
     * @param string $classname
     * @return VirtualFile | null
     */
    public function getVirtualClass(string $classname): ?VirtualFile
    {
        if (isset($this->virtual_classes[$classname])) {
            return $this->virtual_classes[$classname];
        }
        if (isset($this->cache[self::VCLASSES][$classname])){
            return $this->virtual_classes[$classname] = new VirtualFile(...$this->cache[self::VCLASSES][$classname]);
        }
        return null;
    }

    /**
     * @param string $filename
     * @return VirtualFile | null
     */
    public function getVirtualFile(string $filename): ?VirtualFile
    {
        if (isset($this->vfiles[$filename])) {
            return $this->vfiles[$filename];
        }
        if (isset($this->cache[self::VFILES][$filename])){
            return $this->vfiles[$filename] = new VirtualFile(...$this->cache[self::VFILES][$filename]);
        }
        return null;
    }

    /**
     * called on shutdown
     */
    private function saveToCache(): void
    {
        if (!$this->is_modified) {
            return;
        }
        $data = [
            self::CLASS_TOKENS => array_map(fn ($c) => $c->toArray(), $this->class_tokens),
            self::VFILES => array_map(fn ($f) => $f->toArray(), $this->vfiles),
            self::VCLASSES => array_map(fn ($f) => $f->toArray(), $this->virtual_classes),
            'timestamp' => time(),
        ];
        Opcache::write($data);
    }

}