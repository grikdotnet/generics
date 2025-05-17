<?php declare(strict_types=1);

if (!class_exists(ParserTestBase::class, false)) {
    include 'ParserTestBase.php';
}

use grikdotnet\generics\Internal\tokens\{ClassAggregate, MethodHeaderAggregate, Parameter};

final class TemplateDeclarationTest extends ParserTestBase
{
    public function testEmptyClassesDeclaration(): void
    {
        $code = <<<CODE
        <?php
        #[\Generics\T]
        class Foo{}
        class Bar{}
        CODE;
        $fileAggregate = $this->traverse($code);
        self::assertCount(1,$fileAggregate->classAggregates);
        self::assertEquals('Foo',$fileAggregate->classAggregates['Foo']->classname);
        self::assertTrue($fileAggregate->classAggregates['Foo']->isTemplate());
    }

    public function testNamespacedTemplateDeclaration(): void
    {
        $code = '<?php
        namespace ACME;
        #[\Generics\T]
        class Foo{}
        class Bar{}
        
        namespace Foo;
        #[\Generics\T]
        class Bar{}
        ';

        $fileAggregate = $this->traverse($code);
        self::assertCount(2,$fileAggregate->classAggregates);
        self::assertEquals(\ACME\Foo::class,$fileAggregate->classAggregates['ACME\Foo']->getFQCN());
        self::assertEquals('Foo',$fileAggregate->classAggregates['ACME\Foo']->classname);
        self::assertEquals('ACME',$fileAggregate->classAggregates['ACME\Foo']->namespace);
        self::assertEquals(Foo\Bar::class,$fileAggregate->classAggregates['Foo\Bar']->getFQCN());
        self::assertEquals('Bar',$fileAggregate->classAggregates['Foo\Bar']->classname);
        self::assertEquals('Foo',$fileAggregate->classAggregates['Foo\Bar']->namespace);
        self::assertTrue($fileAggregate->classAggregates['ACME\Foo']->isTemplate());
        self::assertTrue($fileAggregate->classAggregates['Foo\Bar']->isTemplate());
    }

    public function testTemplateParameter(): void
    {
        $code = '<?php
        #[\Generics\T]
        class Foo{
            #[\Generics\ReturnT]
            public function __construct(
                int &$x, #[\Generics\T] $param, #[\Generics\T] $y=null, float ... $z
            ){}
            private function bar(){} 
        }';

        $expected = new ClassAggregate('Foo');
        $expected->setIsTemplate();
        $expected->addMethodAggregate(
            $methodAggregate = new MethodHeaderAggregate(
                offset: 60,
                length: 146,
                name: '__construct',
                headline: '#[\Generics\ReturnT] public function __construct( int &$x, #[\Generics\T] $param, #[\Generics\T] $y=null, float ... $z)',
                void: false
            )
        );
        $methodAggregate->setWildcardReturn();
        $methodAggregate->addParameter(new Parameter(offset: 138, length:7,  name: 'x', type:'int &'));
        $methodAggregate->addParameter(new Parameter(offset: 162, length:6, name: 'param', is_wildcard: true));
        $methodAggregate->addParameter(new Parameter(offset: 185, length:2, name: 'y', is_wildcard: true));
        $methodAggregate->addParameter(new Parameter(offset: 194, length:12, name: 'z', type:'float ...'));

        $fileAggregate = $this->traverse($code);
        self::assertEquals('Foo',$fileAggregate->classAggregates['Foo']->classname);
        self::assertEquals($expected, $fileAggregate->classAggregates['Foo']);
    }

    public function testTemplateWithReturnType(): void
    {
        $code = '<?php
        #[\Generics\T]
        class Foo{
            #[\Generics\ReturnT]
            public function foo(
                #[\Generics\T] $param
            ): int{}
            private function bar(){} 
        }';

        $expected = new ClassAggregate('Foo');
        $expected->setIsTemplate();
        $expected->addMethodAggregate(
            $methodAggregate = new MethodHeaderAggregate(
                offset: 60,
                length: 110,
                name: 'foo',
                headline: '#[\Generics\ReturnT] public function foo( #[\Generics\T] $param ): int',
                void: false
            )
        );
        $methodAggregate->setWildcardReturn();
        $methodAggregate->addParameter(new Parameter(offset: 145, length:6, name: 'param', is_wildcard: true));

        $fileAggregate = $this->traverse($code);
        self::assertEquals('Foo',$fileAggregate->classAggregates['Foo']->classname);
        self::assertEquals($expected, $fileAggregate->classAggregates['Foo']);
    }

    public function testReturnVoidAndNamespace(): void
    {
        $code = '<?php
        namespace ACME;
        #[\Generics\T]
        class Foo{
            #[\Generics\ReturnT]
            public function foo(#[\Generics\T] $param): void
            {}
        }';

        $expected = new ClassAggregate('Foo','ACME');
        $expected->setIsTemplate();
        $expected->addMethodAggregate(
            $methodAggregate = new MethodHeaderAggregate(
                offset: 84,
                length: 81,
                name: 'foo',
                headline: '#[\Generics\ReturnT] public function foo(#[\Generics\T] $param): void',
                void: true
            )
        );
        $methodAggregate->setWildcardReturn();
        $methodAggregate->addParameter(new Parameter(offset: 152, length:6, name: 'param', is_wildcard: true));

        $fileAggregate = $this->traverse($code);
        self::assertEquals('Foo',$fileAggregate->classAggregates[ACME\Foo::class]->classname);
        self::assertEquals($expected, $fileAggregate->classAggregates[ACME\Foo::class]);
    }
}
