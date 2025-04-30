<?php

use Generics\Internal\{Container,
    tokens\ClassAggregate,
    tokens\MethodHeaderAggregate,
    tokens\Parameter,
    service\Opcache};

include '../../vendor/autoload.php';

$container = Container::getInstance();
$Opcache = new Opcache();
$Opcache->linkCachedTokens($container);

$class_tokens = [];
$Foo = $container->getClassTokens('Foo');
$Bar = $container->getClassTokens('Bar');

$expected1 = new ClassAggregate('Foo');
$expected1->setIsTemplate();
$expected1->addMethodAggregate(
    $methodAggregate = new MethodHeaderAggregate(offset: 60, length: 156, name: '__construct',headline: 'public function __construct(int &$x, #[\Generics\T] $param)')
);
$methodAggregate->setWildcardReturn();
$methodAggregate->addParameter(new Parameter(offset: 138, length:7,  name: 'x', type:'int &'));
$methodAggregate->addParameter(new Parameter(offset: 162, length:6, name: 'param', is_wildcard: true));


$expected2 = new ClassAggregate('Bar');
$expected2->addMethodAggregate(
    $methodAggregate = new MethodHeaderAggregate(offset: 37,length: 178,name: '__construct',headline: 'public function __construct(#[\Generics\T(Foo)] ACME\Bar $y, #[\Generics\T] $z)')
);
$methodAggregate->addParameter(new Parameter(offset: 106, length: 35, name: 'y', type: "ACME\Bar", concrete_type: "Foo"));
$methodAggregate->addParameter(new Parameter(offset: 159, length: 41, name: 'z', is_wildcard: true));

var_export(($expected1 == $Foo) && ($expected2 == $Bar));
