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

    public function testConcreteParameter(): void
    {
        $code = '<?php
        class Foo{
            public function __construct(
                int $x,
                #[\Generics\T(Foo)] ACME\Bar $param,
                #[\Generics\T("\ACME\Bar<\Qwe\Test>")] $y,
            ){}
        }';
        $expected = new \Generics\Internal\ClassAggregate('','Foo');
        $expected->addMethodAggregate(
            $methodAggregate = new \Generics\Internal\MethodAggregate(offset: 37,length: 178,name: '__construct',)
        );
        $methodAggregate->addParameter(new \Generics\Internal\Parameter(offset: 82, length: 6, name: 'x', type: "int"));
        $methodAggregate->addParameter(new \Generics\Internal\Parameter(
            offset: 106,
            length: 35,
            name: 'param',
            type: "ACME\Bar",
            concrete_type: "Foo"
        ));
        $methodAggregate->addParameter(new \Generics\Internal\Parameter(
            offset: 159,
            length: 41,
            name: 'y',
            type: "\ACME\Bar",
            concrete_type: "\Qwe\Test"
        ));

        $container = $this->traverse($code);
        self::assertEquals($expected, $container->getClassTokens(Foo::class));

    }

}