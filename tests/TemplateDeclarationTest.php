<?php declare(strict_types=1);

use Generics\Internal\Container;
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
            public function __construct(
                int $x, #[\Generics\T] $param, string $y, $z
            ){}
        }';

        $expected = new \Generics\Internal\ParameterTokenAggregate('test','Foo');
        $expected->addToken(new \Generics\Internal\Token(
            offset: 113,
            parameter_name: 'param',
            parameter_type: null,
            type_type: TypeType::Template,
        ));
        $expected->setIsTemplate();
        $expected->current();

        $container = $this->traverse($code);
        self::assertArrayHasKey('Foo',$container->class_tokens);
        $tokens = $container->getClassTokens(Foo::class);
        self::assertCount(1, $tokens);
        self::assertEquals($expected, $tokens);
    }
}