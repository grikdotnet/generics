<?php declare(strict_types=1);

use Generics\Internal\Container;
use Generics\Internal\GenericsVisitor;
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

    private function traverse(string $code): GenericsVisitor {
        $ast = $this->parser->parse($code, new Collecting);
        $container = new Container();
        $visitor = new GenericsVisitor('test',$code);
        $this->traverser->addVisitor($visitor);
        $this->traverser->traverse($ast);
        return $visitor;
    }

    public function testEmptyClassesDeclaration(): void
    {
        $code = <<<CODE
        <?php
        #[\Generics\T]
        class Foo{}
        class Bar{}
        CODE;
        $visitor = $this->traverse($code);
        self::assertCount(1,$visitor->classes);
        self::assertEquals('Foo',$visitor->classes[0]->classname);
        self::assertTrue($visitor->classes[0]->isTemplate());
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

        $visitor = $this->traverse($code);
        self::assertCount(2,$visitor->classes);
        self::assertEquals(ACME\Foo::class,$visitor->classes[0]->classname);
        self::assertEquals(Foo\Bar::class,$visitor->classes[1]->classname);
        self::assertTrue($visitor->classes[0]->isTemplate());
        self::assertTrue($visitor->classes[1]->isTemplate());
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
            private function bar(){} 
        }';

        $expected = new \Generics\Internal\ClassAggregate('test');
        $expected->setClassname('Foo');
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

        $visitor = $this->traverse($code);
        self::assertEquals('Foo',$visitor->classes[0]->classname);
        self::assertEquals($expected, $visitor->classes[0]);
    }

}