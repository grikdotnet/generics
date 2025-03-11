<?php

use Generics\Internal\{ClassAggregate,MethodAggregate,Parameter,Opcache};

include '../../vendor/autoload.php';

$class1 = new ClassAggregate('Foo.php');
$class1->setClassname('Foo');
$class1->setIsTemplate();
$class1->addMethodAggregate(
    $methodAggregate = new MethodAggregate(offset: 60, length: 156, name: '__construct',)
);
$methodAggregate->setWildcardReturn();
$methodAggregate->addParameter(new Parameter(offset: 138, length:7,  name: 'x', type:'int &'));
$methodAggregate->addParameter(new Parameter(offset: 162, length:6, name: 'param', is_wildcard: true));


$class2 = new ClassAggregate('Bar.php');
$class2->setClassname('Bar');
$class2->addMethodAggregate(
    $methodAggregate = new MethodAggregate(offset: 37,length: 178,name: '__construct',)
);
$methodAggregate->addParameter(new Parameter(offset: 106, length: 35, name: 'y', type: "ACME\Bar", concrete_type: "Foo"));
$methodAggregate->addParameter(new Parameter(offset: 159, length: 41, name: 'z', is_wildcard: true));

$data = [100=>[
    $class1->classname => $class1->toArray(),
    $class2->classname => $class2->toArray(),
]];

try {
    Opcache::write($data);
} catch (Error $e) {
    header('HTTP/1.1 500 Opcache error');
    throw $e;
}
