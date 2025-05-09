<?php

use Generics\Internal\{Container,
    tokens\ClassAggregate,
    tokens\FileAggregate,
    tokens\MethodHeaderAggregate,
    tokens\Parameter,
    service\Opcache};

include '../../vendor/autoload.php';

$class1 = new ClassAggregate('Foo');
$class1->setIsTemplate();
$class1->addMethodAggregate(
    $methodAggregate = new MethodHeaderAggregate(offset: 60, length: 156, name: '__construct',headline: 'public function __construct(int &$x, #[\Generics\T] $param)')
);
$methodAggregate->setWildcardReturn();
$methodAggregate->addParameter(new Parameter(offset: 138, length:7,  name: 'x', type:'int &'));
$methodAggregate->addParameter(new Parameter(offset: 162, length:6, name: 'param', is_wildcard: true));


$class2 = new ClassAggregate('Bar');
$class2->addMethodAggregate(
    $methodAggregate = new MethodHeaderAggregate(offset: 37,length: 178,name: '__construct',headline: 'public function __construct(#[\Generics\T(Foo)] ACME\Bar $y, #[\Generics\T] $z)')
);
$methodAggregate->addParameter(new Parameter(offset: 106, length: 35, name: 'y', type: "ACME\Bar", concrete_types: ["Foo"]));
$methodAggregate->addParameter(new Parameter(offset: 159, length: 41, name: 'z', is_wildcard: true));

$fileToken = new FileAggregate('\test\Foo.php',['Foo'=>$class1,'Bar'=>$class2],[]);

$container = Container::getInstance();
$container->addFileTokens($fileToken);

$Opcache = new Opcache();
try {
    $Opcache->store($container);
} catch (Error $e) {
    header('HTTP/1.1 500 Opcache error');
    throw $e;
}

var_export(opcache_is_script_cached('phar:///generics/cache@/opt/projects/generics/Generics/Internal/service'));