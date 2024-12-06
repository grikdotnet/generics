<?php declare(strict_types=1);

namespace Generics;

use Composer\Autoload\ClassLoader;
use Generics\Internal\{Container, StreamWrapper};

/**
 * @api
 */
final class Enable
{
    private static bool $enabled = false;

    /**
     * Turns on processing og generics for PHP
     * @param ClassLoader|null $loader
     * @return void
     */
    public function __construct(?ClassLoader $loader=null)
    {
        if (self::$enabled) {
            return;
        }
        self::$enabled = true;
        if (!$loader) {
            $loader = $this->getComposerLoader();
        }

        // avoid an infinite loop in autoloader
        $this->preloadInternals();

        $container = new Container();

        StreamWrapper::register($container);
        $this->registerAutoloader($loader,$container);
    }

    private function registerAutoloader($loader, Container $container):void
    {
        /**
         * Make Composer load the class files with generics through a stream wrapper
         */
        spl_autoload_register(function ($class) use ($loader, $container): false {
            $path = $loader->findFile($class);
            if (str_starts_with($path, 'generic://')) {
                return false;
            }
            if (str_starts_with($path, 'file://')) {
                $path = substr($path,7);
            }

            // true, false or null
            $file_contain_generics = $container->cache->hasGenerics($path);

            if ($file_contain_generics) {
                $loader->addClassMap([$class => 'generic://'.$path]);
            }
            if ($file_contain_generics === null && file_exists($path) && is_readable($path)) {
                $code = file_get_contents($path);
                if (str_contains($code,'Generics\\')) {
                    $container->cache->setFileCache($path,$code);
                    $container->cache->setHasGenerics($path, true);
                    $loader->addClassMap([$class => 'generic://'.$path]);
                }else{
                    $container->cache->setHasGenerics($path, false);
                }
            }

            return false;
        },true,true);
    }

    /**
     * @return mixed
     */
    public function getComposerLoader(): ClassLoader
    {
        $composer_autoload = dirname(__DIR__) . '/autoload.php';
        if (!is_file($composer_autoload) || !is_readable($composer_autoload)) {
            throw new \RuntimeException('Can not obtain a Composer loader');
        }
        return include $composer_autoload;
    }

    private function preloadInternals():void
    {
        foreach (new \DirectoryIterator(__DIR__ . '/Internal') as $file){
            $filename = $file->getFilename();
            if(!$file->isDot() && !class_exists('\Generics\_internal\\'.substr($file->getFilename(),0,4),false)) {
                include __DIR__ . '/Internal/' .$filename;
            }
        }
        include 'T.php';
        include 'ParameterType.php';
    }

}
