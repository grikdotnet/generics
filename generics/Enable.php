<?php declare(strict_types=1);

namespace grikdotnet\generics;

use Composer\Autoload\ClassLoader;
use grikdotnet\generics\Internal\{Container, Loader};

/**
 * A wrapper to provide access from anywhere
 * @api
 */
final class Enable
{
    private static bool $enabled = false;

    /**
     * Turns on processing og generics for PHP
     * @param ClassLoader|null $composer
     */
    public function __construct(?ClassLoader $composer=null)
    {
        if (self::$enabled) {
            return;
        }
        self::$enabled = true;

        $container = Container::getInstance();

        $loader = new Loader($container, $composer);
        Concrete::setLoader($loader);
    }

    public static function enabled(): bool
    {
        return self::$enabled;
    }
}
