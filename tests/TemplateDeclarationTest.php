<?php declare(strict_types=1);

use Generics\Internal\Container;
use Generics\Internal\Parameter;
use Generics\Internal\TypeType;
use PhpParser\ErrorHandler\Collecting;
use PhpParser\Lexer;
use PhpParser\NodeTraverser;
use PhpParser\Parser\Php8;
use PHPUnit\Framework\TestCase;

final class TemplateDeclarationTest extends TestCase
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
        $this->traverser->addVisitor(new \Generics\Internal\GenericsVisitor('test',$code, $container));
        $this->traverser->traverse($ast);
        return $container;
    }

    public function testEmptyClassesDeclaration(): void
    {
        $code = <<<CODE
        <?php
        #[\Generics\T]
        class Foo{}
        class Bar{}
        CODE;
        $container = $this->traverse($code);
        self::assertCount(1,$container->class_tokens);
        self::assertArrayHasKey('Foo',$container->class_tokens);
        self::assertArrayNotHasKey('Bar',$container->class_tokens);
        self::assertTrue($container->isClassTemplate('Foo'));
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

        $container = $this->traverse($code);
        self::assertCount(2,$container->class_tokens);
        self::assertArrayHasKey(ACME\Foo::class,$container->class_tokens);
        self::assertArrayHasKey(Foo\Bar::class,$container->class_tokens);
        self::assertArrayNotHasKey(ACME\Bar::class,$container->class_tokens);
        self::assertTrue($container->isClassTemplate(ACME\Foo::class));
        self::assertTrue($container->isClassTemplate(\Foo\Bar::class));
    }

    public function testTemplateParameter(): void
    {
        $code = '<?php
        #[\Generics\T]
        class Foo{
            #[\Generics\ReturnT]
            public function __construct(
                int &$x, #[\Generics\T] $param, ?\ACME\Bar $y=null, float ... $z
            ){}
        }';

        $expected = new \Generics\Internal\ClassAggregate('test','Foo');
        $expected->setIsTemplate();
        $expected->addMethodAggregate(
            $methodAggregate = new \Generics\Internal\MethodAggregate(
                offset: 60,
                length: 156,
                name: '__construct',
            )
        );
        $methodAggregate->setWildcardReturn();
        $methodAggregate->addParameter(new Parameter(offset: 138, length:7,  name: 'x', type:'int &'));
        $methodAggregate->addParameter(new Parameter(offset: 162, length:6, name: 'param', is_wildcard: true));
        $methodAggregate->addParameter(new Parameter(offset: 170, length:13, name: 'y', type:'?\ACME\Bar'));
        $methodAggregate->addParameter(new Parameter(offset: 190, length:12, name: 'z', type:'float ...'));

        $container = $this->traverse($code);
        self::assertArrayHasKey('Foo',$container->class_tokens);
        $tokens = $container->getClassTokens(Foo::class);
        self::assertEquals($expected, $tokens);
    }

}