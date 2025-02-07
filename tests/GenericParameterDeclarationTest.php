<?php declare(strict_types=1);

use Generics\Internal\Container;
use PhpParser\ErrorHandler\Collecting;
use PhpParser\Lexer;
use PhpParser\NodeTraverser;
use PhpParser\Parser\Php8;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class GenericParameterDeclarationTest extends TestCase
{
    private Php8 $parser;
    private NodeTraverser $traverser;

    public function setUp(): void
    {
        $this->parser = new Php8(new Lexer);
        $this->traverser = new NodeTraverser;
        $this->traverser->addVisitor(new PhpParser\NodeVisitor\NameResolver);
    }

    public function tearDown(): void
    {
        unset($this->traverser);
    }

    private function traverse(string $code): Container {
        $ast = $this->parser->parse($code, new Collecting);
        $container = new Container();
        $this->traverser->addVisitor(new \Generics\Internal\GenericsVisitor('',$code, $container));
        $this->traverser->traverse($ast);
        return $container;
    }


    public function testGenericUnionParameter(): void
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
        return;

        $code = '<?php
        class Foo{
            public function __construct(
                int $x, #[\Generics\T(\ACME\Bar|false)] $param, string $y
            ){}
        }';
        $expected = new \Generics\Internal\ClassAggregate('','Foo');
        $methodAggregate = new \Generics\Internal\MethodAggregate(
            name: '__construct',
            offset: 37,
            length: 116,
            parameters_offset: 82,
        );
        $methodAggregate->addParameterToken(new \Generics\Internal\UnionParameterToken(
            offset: 90,
            length: 38,
            types: ['false','\ACME\Bar']
        ));
        $expected->addMethodAggregate($methodAggregate);

        $container = $this->traverse($code);
        self::assertCount(1, $container->class_tokens);
        self::assertEquals($expected, $container->getClassTokens(Foo::class));
    }

    public function testConcreteParameter(): void
    {
        $code = '<?php
        class Foo{
            public function __construct(
                int $x,
                #[\Generics\T(Foo)] ACME\Bar $param,
                string $a,
                #[\Generics\T("\ACME\Bar<\Qwe\Test>")] $c,
                #[\Generics\T("ACME\Bar<float>")] $d,
            ){}
        }';
        $expected = new \Generics\Internal\ClassAggregate('','Foo');
        $methodAggregate = new \Generics\Internal\MethodAggregate(
            name: '__construct',
            offset: 37,
            length: 265,
            parameters_offset: 82
        );
        $methodAggregate->addParameterToken(new \Generics\Internal\ConcreteParameterToken(
            offset: 151,
            length: 43,
            base_type: "ACME\Bar",
            concrete_type: "int"
        ));

        $container = $this->traverse($code);
        self::assertCount(1, $container->class_tokens);
        self::assertEquals($expected, $container->getClassTokens(Foo::class));

        $code = '<?php
        class Foo{
            public function __construct(
                int $x, #[\Generics\ParameterType(Bar)] $param, string $y
            ){}
        }';
        $expected = new \Generics\Internal\ClassAggregate('','Foo');
        $expected->addMethodAggregate(new \Generics\Internal\ConcreteParameterToken(
            offset: 122,
            length: 38,
            base_type: "Bar",
        ));
        $container = $this->traverse($code);
        self::assertEquals($expected, $container->getClassTokens(Foo::class));

    }

}