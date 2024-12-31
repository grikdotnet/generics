<?php declare(strict_types=1);

use Generics\Internal\Container;
use PhpParser\ErrorHandler\Collecting;
use PhpParser\Lexer;
use PhpParser\NodeTraverser;
use PhpParser\Parser\Php8;
use PHPUnit\Framework\TestCase;

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
        $code = '<?php
        class Foo{
            public function __construct(
                int $x, #[\Generics\ParameterType(\ACME\Bar|false)] $param, string $y
            ){}
        }';
        $expected = new \Generics\Internal\ParameterTokenAggregate('','Foo');
        $expected->addToken(new \Generics\Internal\UnionToken(
            offset: 90,
            parameter_name: 'param',
            union_types: ['false','\ACME\Bar']
        ));
        $container = $this->traverse($code);
        self::assertCount(1, $container->class_tokens);
        self::assertEquals($expected, $container->getClassTokens(Foo::class));
    }

    public function testGenericParameter(): void
    {
        $code = '<?php
        class Foo{
            public function __construct(
                int $x, 
                #[\Generics\ParameterType(ACME\Bar::class)] $param, 
                string $a,
                #[\Generics\ParameterType(ACME\Bar)] $b, 
                #[\Generics\ParameterType(\ACME\Bar)] $c, 
                #[\Generics\ParameterType("ACME\Bar")] $d, 
                #[\Generics\ParameterType("int")] $e, 
            ){}
        }';
        $expected = new \Generics\Internal\ParameterTokenAggregate('','Foo');
        $expected->addToken(new \Generics\Internal\Token(
            offset: 151,
            parameter_name: 'param',
            parameter_type: "ACME\Bar"
        ));
        $expected->addToken(new \Generics\Internal\Token(
            offset: 240,
            parameter_name: 'b',
            parameter_type: "ACME\Bar"
        ));
        $expected->addToken(new \Generics\Internal\Token(
            offset: 299,
            parameter_name: 'c',
            parameter_type: "\ACME\Bar"
        ));
        $expected->addToken(new \Generics\Internal\Token(
            offset: 359,
            parameter_name: 'd',
            parameter_type: "ACME\Bar"
        ));
        $expected->addToken(new \Generics\Internal\Token(
            offset: 414,
            parameter_name: 'e',
            parameter_type: "int"
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
        $expected = new \Generics\Internal\ParameterTokenAggregate('','Foo');
        $expected->addToken(new \Generics\Internal\Token(
            offset: 122,
            parameter_name: 'param',
            parameter_type: "Bar"
        ));
        $container = $this->traverse($code);
        self::assertEquals($expected, $container->getClassTokens(Foo::class));

    }

}