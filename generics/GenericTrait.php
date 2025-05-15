<?php declare(strict_types=1);

namespace grikdotnet\generics;

use Generics\T;

trait GenericTrait {

    /**
     * @template T
     * @param string|class-string<T> $type
     * @return self
     */
    static public function T(string ... $types): string
    {
        if (!Enable::enabled()) {
            throw new \RuntimeException('Generics processing is not enabled');
        }
        if ([] === (new \ReflectionClass(__CLASS__))->getAttributes(T::class)) {
            throw new \RuntimeException('The class '.__CLASS__.' is not a generic template');
        }
        return Concrete::createClass(__CLASS__,$types);
    }

}