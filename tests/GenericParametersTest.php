<?php declare(strict_types=1);
if (!class_exists(ParserTestBase::class, false)) {
    include 'ParserTestBase.php';
}

use grikdotnet\generics\Internal\tokens\{ClassAggregate, FileAggregate, MethodHeaderAggregate, Parameter};

final class GenericParametersTest extends ParserTestBase
{

    public function testConcreteParameters(): void
    {
        $code = '<?php
        class Foo{
            public function __construct(
                int $x,
                #[  Generics\T ( Foo )] ACME\Bar $param,
                #[\Generics\T("\ACME\Bar<\Qwe\Test>")] $y,
            ){}
        }';
        $classAggregate = new ClassAggregate('Foo','');
        $classAggregate->addMethodAggregate(
            $methodAggregate = new MethodHeaderAggregate(
                offset: 37,length: 167,name: '__construct',
                headline:'public function __construct( int $x, #[ Generics\T ( Foo )] ACME\Bar $param, #[\Generics\T("\ACME\Bar<\Qwe\Test>")] $y)',
                void: false
            )
        );
        $methodAggregate->addParameter(new Parameter(offset: 82, length: 6, name: 'x', type: "int"));
        $methodAggregate->addParameter(new Parameter(
            offset: 106,
            length: 39,
            name: 'param',
            type: "ACME\Bar",
            concrete_types: ["Foo"]
        ));
        $methodAggregate->addParameter(new Parameter(
            offset: 163,
            length: 41,
            name: 'y',
            type: "\ACME\Bar",
            concrete_types: ["\Qwe\Test"]
        ));
        $expected = new FileAggregate('',['Foo'=>$classAggregate],[]);

        $actual = $this->traverse($code);
        self::assertEquals($expected, $actual);
    }

    public function testMultipleTypeParameter(): void
    {
        $code = '<?php
        class Foo{
            public function foo(
                #[\Generics\T(\MyClass, float)] \ACME\Bar $param1,
                #[\Generics\T("ACME\Bar<MyClass><int>")] ACME\Bar $param2,
            ){}
        }';
        $headline = 'public function foo( #[\Generics\T(\MyClass, float)] \ACME\Bar $param1, #[\Generics\T("ACME\Bar<MyClass><int>")] ACME\Bar $param2)';
        $classAggregate = new ClassAggregate('Foo');
        $classAggregate->addMethodAggregate(
            $methodAggregate = new MethodHeaderAggregate(
                offset: 37,length: 161,name: 'foo',
                headline: $headline
            )
        );
        $methodAggregate->addParameter(new Parameter(
            offset: 74,
            length: 49,
            name: 'param1',
            type: "\ACME\Bar",
            concrete_types: ["\MyClass",'float']
        ));
        $methodAggregate->addParameter(new Parameter(
            offset: 141,
            length: 57,
            name: 'param2',
            type: "ACME\Bar",
            concrete_types: ["MyClass",'int']
        ));
        $expected = new FileAggregate('',['Foo'=>$classAggregate],[]);

        $actual = $this->traverse($code);
        self::assertEquals($expected, $actual);
    }


    public function testParametersInNamespace(): void
    {
        $code = '<?php
        namespace ACME; 
        class Foo{
            public function func(
                #[\Generics\T("Bar<\Qwe\Test>")] $y,
            ){}
        }';
        $classAggregate = new ClassAggregate('Foo','ACME');
        $classAggregate->addMethodAggregate(
            $methodAggregate = new MethodHeaderAggregate(
                offset: 62,length: 73,name: 'func',
                headline:'public function func( #[\Generics\T("Bar<\Qwe\Test>")] $y)',
                void: false
            )
        );
        $methodAggregate->addParameter(new Parameter(
            offset: 100,
            length: 35,
            name: 'y',
            type: "Bar",
            concrete_types: ["\Qwe\Test"]
        ));
        $expected = new FileAggregate('',['ACME\Foo'=>$classAggregate],[]);

        $actual = $this->traverse($code);
        self::assertEquals($expected, $actual);
    }
}
