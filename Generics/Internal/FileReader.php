<?php declare(strict_types=1);

namespace Generics\Internal;

/**
 * @internal
 */
class FileReader
{
    /** @var array<string, string> */
    private array $file_cache;

    private array $files_with_generics = [];

    /**
     * @param string $path
     * @return bool|null
     */
    public function hasGenerics(string $path): ?bool
    {
        if (isset($this->files_with_generics[$path])) {
            return $this->files_with_generics[$path];
        }
        $content = $this->getFile($path);
        $result = $this->files_with_generics[$path] = str_contains($content,'Generics\\');
        if ($result) {
            $this->file_cache[$path] = $content;
        }
        return $result;
    }

    public function getFile(string $path): string|false
    {
        if (isset($this->file_cache[$path])) {
            return $this->file_cache[$path];
        }
        if (file_exists($path) && is_readable($path)) {
            return file_get_contents($path);
        }
        return false;
    }

    public function clearFileCache(string $path): void
    {
        unset($this->file_cache[$path]);
    }

}