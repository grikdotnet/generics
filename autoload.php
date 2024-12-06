<?php

spl_autoload_register(function ($name){
    if (!str_starts_with($name, 'grikdotnet\GenericCollection\Collection')) {
        return false;
    }
    $separator1 = mb_substr($name,39,1);
    $separator2 = mb_substr($name,-1);
    $type = mb_substr($name,40,-1);
    $class_short_name = substr($name,strrpos($name,'\\')+1);

    $generic_class_code = "namespace Generics\Implementation;
    class $class_short_name extends \ArrayObject {
        public function __construct(\\$type ...\$args){
            parent::__construct(...\$args);
        }
    }";

    eval($generic_class_code);

    return true;
});
