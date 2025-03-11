<?php

use Generics\Internal\{ClassAggregate,MethodAggregate,Parameter,Opcache};

include '../../vendor/autoload.php';

$data = Opcache::read();

$class_tokens = [];
$Foo = $Bar = null;
if (isset($data[100]['Foo']) && isset($data[100]['Bar'])){
    $Foo = ClassAggregate::fromArray($data[100]['Foo']);
    $Bar = ClassAggregate::fromArray($data[100]['Bar']);
}

$expected1 = new ClassAggregate('Foo.php');
$expected1->setClassname('Foo');
$expected1->setIsTemplate();
$expected1->addMethodAggregate(
    $methodAggregate = new MethodAggregate(offset: 60, length: 156, name: '__construct',)
);
$methodAggregate->setWildcardReturn();
$methodAggregate->addParameter(new Parameter(offset: 138, length:7,  name: 'x', type:'int &'));
$methodAggregate->addParameter(new Parameter(offset: 162, length:6, name: 'param', is_wildcard: true));


$expected2 = new ClassAggregate('Bar.php');
$expected2->setClassname('Bar');
$expected2->addMethodAggregate(
    $methodAggregate = new MethodAggregate(offset: 37,length: 178,name: '__construct',)
);
$methodAggregate->addParameter(new Parameter(offset: 106, length: 35, name: 'y', type: "ACME\Bar", concrete_type: "Foo"));
$methodAggregate->addParameter(new Parameter(offset: 159, length: 41, name: 'z', is_wildcard: true));

try {
    var_export(assert($expected1 == $Foo) && assert($expected2 == $Bar));
}catch(AssertionError $e){
    header('HTTP/1.1 500 Opcache error');
    echo 'AssertionError: '.$e->getMessage();
}
