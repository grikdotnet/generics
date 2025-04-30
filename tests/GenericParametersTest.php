<?php declare(strict_types=1);
if (!class_exists(ParserTestBase::class, false)) {
    include 'ParserTestBase.php';
}

use Generics\Internal\tokens\FileAggregate;

final class GenericParametersTest extends ParserTestBase
{

    public function testConcreteParameters(): void
    {
        $code = '<?php
        class Foo{
            public function __construct(
                int $x,
                #[ \Generics\T ( Foo )] ACME\Bar $param,
                #[\Generics\T("\ACME\Bar<\Qwe\Test>")] $y,
            ){}
        }';
        $classAggregate = new \Generics\Internal\tokens\ClassAggregate('Foo');
        $classAggregate->addMethodAggregate(
            $methodAggregate = new \Generics\Internal\tokens\MethodHeaderAggregate(
                offset: 37,length: 167,name: '__construct',
                headline:'public function __construct( int $x, #[ \Generics\T ( Foo )] ACME\Bar $param, #[\Generics\T("\ACME\Bar<\Qwe\Test>")] $y)'
            )
        );
        $methodAggregate->addParameter(new \Generics\Internal\tokens\Parameter(offset: 82, length: 6, name: 'x', type: "int"));
        $methodAggregate->addParameter(new \Generics\Internal\tokens\Parameter(
            offset: 106,
            length: 39,
            name: 'param',
            type: "ACME\Bar",
            concrete_type: "Foo"
        ));
        $methodAggregate->addParameter(new \Generics\Internal\tokens\Parameter(
            offset: 163,
            length: 41,
            name: 'y',
            type: "\ACME\Bar",
            concrete_type: "\Qwe\Test"
        ));
        $expected = new FileAggregate('',['Foo'=>$classAggregate],[]);

        $actual = $this->traverse($code);
        self::assertEquals($expected, $actual);
    }
}
