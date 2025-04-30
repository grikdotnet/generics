<?php declare(strict_types=1);

namespace Generics;

trait GenericTrait {

    /**
     * @template T
     * @param string|class-string<T> $type
     * @throws \ReflectionException
     */
    static public function T(string $type): \Closure
    {
        if (!Enable::enabled()) {
            throw new \RuntimeException('Generics processing is not enabled');
        }
        if ([] === (new \ReflectionClass(__CLASS__))->getAttributes(T::class)) {
            throw new \RuntimeException('The class '.__CLASS__.' is not a generic template');
        }
        $target_class = Concrete::createClass(__CLASS__,$type);
        /** @var $instance self */
        $instance = (new \ReflectionClass($target_class))->newInstanceWithoutConstructor();
        /**
         * @template T
         * @return self
         */
        return function (...$args) use ($instance) {
            if (method_exists($instance,'__construct') ) {
                $instance->__construct(...$args);
            }
            return $instance;
        };
    }

}