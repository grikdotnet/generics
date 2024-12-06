<?php declare(strict_types=1);

namespace Generics\Internal;

/**
 * @internal
 */
class FileCache
{
    /** @var array<string, string> */
    private array $file_cache;

    private array $have_generics = [];

    /**
     * @param string $path
     * @return bool|null
     */
    public function hasGenerics(string $path): ?bool
    {
        if (isset($this->have_generics[$path])) {
            return $this->have_generics[$path];
        }
        return null;
    }

    public function setHasGenerics(string $path, bool $flag): void
    {
        $this->have_generics[$path] = $flag;
    }

    public function getFileCache(string $path): string|false
    {
        return $this->file_cache[$path] ?? false;
    }

    public function setFileCache(string $path, string $data):void
    {
        $this->file_cache[$path] = $data;
    }

    public function dropFileCache(string $path): void
    {
        unset($this->file_cache[$path]);
    }

}