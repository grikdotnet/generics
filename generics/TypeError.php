<?php

namespace grikdotnet\generics;

class TypeError extends \TypeError {
    private array $trace = [];

    /**
     * @param \TypeError $e
     * @return self
     */
    static public function fromTypeError(\TypeError $e): self
    {
        $trace = $e->getTrace();
        $pattern = '~Argument #(\d+ \(\$\S+\)) must be of type (\S+, \S+) given~';
        if (!isset($trace[0]) || !preg_match($pattern,$e->message,$matches)) {
            return new self($e->message,$e->code);
        }

        array_shift($trace);
        if (isset($trace[0]["class"])){
            $message = $trace[0]["class"].'::';
        } else {
            $message = '';
        }
        $message.= $trace[0]["function"].': Argument #'.$matches[1].' must be of type '.$matches[2].' given';

        $genericTypeError = new self($message,$e->code);
        $genericTypeError->file = $e->file;
        $genericTypeError->line = $e->line;

        return self::setTrace($genericTypeError,$trace);
    }

    private static function setTrace(self $e, array $new_trace): self
    {
        $serialized = serialize($e);
        $start = strpos($serialized,"\0trace\";a");
        $end = strpos($serialized,"s:15:\"\0Error\0previous",$start+5);
        $new_serialize = substr($serialized,0,$start+8).serialize($new_trace).substr($serialized,$end);
        return unserialize($new_serialize);
    }
}