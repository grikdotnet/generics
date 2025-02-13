<?php declare(strict_types=1);

namespace Generics;

class Concrete {

    public static function new(\Closure $arrow, string $type)
    {
        $r = new \ReflectionFunction($arrow);
        if (!Enable::enabled() || $r->getAttributes('Generics\T') == []){
            return $r->invoke();
        }
        $db = \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS);
        $file = $db[0]['file'] ?? '';
        $line = $db[0]['line'] ?? null;
        if (!$file || !$line) {

        }
    }
}