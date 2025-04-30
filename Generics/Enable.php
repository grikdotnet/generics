<?php declare(strict_types=1);

namespace Generics;

use Composer\Autoload\ClassLoader;
use Generics\Internal\{Loader, Container};

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
     * @return void
     */
    public function __construct(?ClassLoader $composer=null)
    {
        if (self::$enabled) {
            return;
        }
        self::$enabled = true;

        $container = Container::getInstance();

        $loader = new Loader($container);
        Concrete::setLoader($loader);
    }

    public static function enabled(): bool
    {
        return self::$enabled;
    }
}
