<?php

use Generics\Internal\tokens\ClassAggregate;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use Generics\Internal\tokens\Parameter;

class OpcacheTest extends TestCase {

    public function testArrayConversion()
    {
        $classAggregate = new ClassAggregate('Foo');
        $classAggregate->setIsTemplate();
        $classAggregate->addMethodAggregate(
            $methodAggregate = new \Generics\Internal\tokens\MethodHeaderAggregate(
                offset: 60,
                length: 156,
                name: '__construct',
                headline: 'public function __construct(int &$x, #[\Generics\T] $param, ?\ACME\Bar $y=null)'
            )
        );
        $methodAggregate->setWildcardReturn();
        $methodAggregate->addParameter(new Parameter(offset: 138, length:7,  name: 'x', type:'int &'));
        $methodAggregate->addParameter(new Parameter(offset: 162, length:6, name: 'param', is_wildcard: true));
        $methodAggregate->addParameter(new Parameter(offset: 170, length:13, name: 'y', type:'?\ACME\Bar'));

        $as_array = $classAggregate->toArray();
        $unserialized = ClassAggregate::fromArray($as_array);

        self::assertEquals($classAggregate,$unserialized);
    }

}
