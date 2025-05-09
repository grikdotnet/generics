<?php declare(strict_types=1);
if (!class_exists(ParserTestBase::class, false)) {
    include 'ParserTestBase.php';
}

use Generics\Internal\view\Transformer;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;

final class TransformerTest extends ParserTestBase
{
    public function testParameterAugmentation(): void{
        $code = '<?php
        class Foo{
            public function __construct(
                #[\Generics\T(Foo)] ACME\Bar $param,
                #[\Generics\T("\ACME\Bar<\Qwe\Test><int>")] $y,
            ){}
        }';
        $fileAggregate = $this->traverse($code);
        $augmented = Transformer::augment($code, $fileAggregate);
        $expected = '<?php
        class Foo{
            public function __construct(
                ACME\Bar‹Foo› $param,
                \ACME\Bar‹⧵Qwe⧵Test›‹int› $y,
            ){}
        }';
        self::assertEquals($expected, $augmented);
    }

    public function testParsingNewInstanceToken()
    {
        $code = '<?php 
            new StdClass;
            fn() => new ShouldBeSkipped($x);
            $c = (#[Generics\T("int")] fn() => new Acme\Foo($x))();
            (#[Generics\T(MyClass)] fn() => new \Acme\Bar($y))();
            (fn() => new SkipMe())();
            ';
        $expected = [];

        $expected[129] = new \Generics\Internal\tokens\ConcreteInstantiationToken(
            offset: 129,
            length: 8,
            type: "Acme\Foo",
            concrete_types: ["int"]
        );
        $expected[194] = new \Generics\Internal\tokens\ConcreteInstantiationToken(
            offset: 194,
            length: 9,
            type: "\Acme\Bar",
            concrete_types: ["MyClass"]
        );

        $fileAggregate = $this->traverse($code);
        self::assertEquals($expected, $fileAggregate->instantiations);
    }

    #[WithoutErrorHandler]
    public function testNamespacedInstanceToken()
    {
        $code = '<?php namespace ACME;
            $c = (#[\Generics\T(\Acme\Bar)] fn() => new Foo($x))(); ';

        $expected[78] = new \Generics\Internal\tokens\ConcreteInstantiationToken(
            offset: 78,
            length: 3,
            type: "Foo",
            concrete_types: ["\Acme\Bar"]
        );

        $fileAggregate = $this->traverse($code);
        self::assertEquals($expected, $fileAggregate->instantiations);
    }

    #[WithoutErrorHandler]
    public function testConcreteInstantiationSubstitution()
    {
        $code = '<?php $c = (#[Generics\T(\Acme\Bar)] fn() => new Foo($x))(); ';

        $expectedToken = new \Generics\Internal\tokens\ConcreteInstantiationToken(
            offset: 49,
            length: 3,
            type: "Foo",
            concrete_types: ["\Acme\Bar"],
        );

        $fileAggregate = $this->traverse($code);

        $expected_code = '<?php $c = (#[Generics\T(\Acme\Bar)] fn() => new Foo‹⧵Acme⧵Bar›($x))(); ';
        $augmented = Transformer::augment($code, $fileAggregate);
        self::assertCount(1, $fileAggregate->instantiations);
        self::assertEquals([49=>$expectedToken], $fileAggregate->instantiations);
        self::assertEquals($expected_code, $augmented);
    }

}
