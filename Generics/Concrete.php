<?php declare(strict_types=1);

namespace Generics;

use Generics\Internal\Loader;
use Generics\Internal\view\ConcreteView;

/**
 * An entry point for the trait to generate a concrete classes from a wildcard template
 * @api
 */
class Concrete {
    protected static Loader $loader;

    /**
     * @param Loader $loader
     */
    public static function setLoader(Loader $loader): void
    {
        self::$loader = $loader;
    }

    /**
     * @param class-string $wildcard_class_name
     * @param string $type
     * @return string
     * @throws \RuntimeException
     */
    public static function createClass(string $wildcard_class_name, string $type): string
    {
        if (!Enable::enabled()) {
            throw new \RuntimeException('Generics processing is not enabled');
        }
        $concrete_class_name = ConcreteView::makeConcreteName($wildcard_class_name, $type);
        //does virtual class declaration exist?
        if (class_exists($concrete_class_name,false)
            || self::$loader->createConcreteClass($wildcard_class_name,$type)
        ){
            return $concrete_class_name;
        }
        throw new \RuntimeException('Could not create concrete class '.$concrete_class_name);
    }

}