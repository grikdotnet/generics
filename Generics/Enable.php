<?php declare(strict_types=1);

namespace Generics;

use Composer\Autoload\ClassLoader;
use Generics\Internal\{Container, StreamWrapper};
use http\Exception\RuntimeException;

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
        if (!$loader){
            throw new RuntimeException("A Composer loader could not be found");
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
            }elseif (preg_match('~^\w+://~',$path)) {
                return false;
            }
            if ($container->reader->hasGenerics($path)) {
                $loader->addClassMap([$class => 'generic://'.$path]);
            }
            return false;
        },true,true);
    }

    /**
     * @return mixed
     */
    public function getComposerLoader(): ClassLoader
    {
        $composer_path = dirname(__DIR__) . '/autoload.php';
        is_file($composer_path) && is_readable($composer_path) && $composer = include $composer_path;

        if (! ($composer ?? null) instanceof \Composer\Autoload\ClassLoader) {
            throw new \RuntimeException('Could not obtain a Composer loader');
        }
        return $composer;
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
    }

}
