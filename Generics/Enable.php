<?php declare(strict_types=1);

namespace Generics;

use Composer\Autoload\ClassLoader;
use Generics\Internal\{Autoloader, Container, StreamWrapper};

/**
 * @api
 */
final class Enable
{
    private static bool $enabled = false;

    /**
     * Turns on processing og generics for PHP
     * @param ClassLoader|null $composer
     * @return void
     */
    public function __construct(?ClassLoader $composer=null)
    {
        if (self::$enabled) {
            return;
        }
        self::$enabled = true;

        // avoid an infinite loop in autoloader
//        $this->preloadInternals();

        $container = new Container();

        StreamWrapper::register($container);
        new Autoloader($container);
    }

    /**
     * @return mixed
     */
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

    public static function enabled(): bool
    {
        return self::$enabled;
    }

}
