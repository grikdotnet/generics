<?php declare(strict_types=1);

namespace Generics\Internal\service;

use Composer\Autoload\ClassLoader;

/**
 * @internal
 */
class ComposerAdapter {
    /**
     * @var array<ClassLoader>
     */
    private array $loaders;
    public function __construct(ClassLoader ... $loaders)
    {
        $this->loaders = $loaders;
    }

    public function findClassFile(string $class): string | false
    {
        if (str_starts_with($class, 'Generics\\')) {
            return false;
        }
        $path = false;
        foreach ($this->loaders as $l) {
            if ($path = $l->findFile($class)) {
                break;
            }
        }
        if (!$path) {
            return false;
        }
        if (str_starts_with($path, 'file://')) {
            $path = substr($path,7);
        } elseif (preg_match('~^\w+://~',$path)) {
            return false;
        }
        return $path;
    }
}