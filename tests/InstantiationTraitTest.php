<?php declare(strict_types=1);

use Generics\Internal\GenericsVisitor;
use PhpParser\ErrorHandler\Collecting;
use PhpParser\Lexer;
use PhpParser\NodeTraverser;
use PhpParser\Parser\Php8;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;
use PHPUnit\Framework\TestCase;

class InstantiationTraitTest extends TestCase
{
    private Php8 $parser;
    private NodeTraverser $traverser;

    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->parser = new Php8(new Lexer);
        $this->traverser = new NodeTraverser;
        new \Generics\Enable();
    }

    private function traverse(string $code): GenericsVisitor {
        $ast = $this->parser->parse($code, new Collecting);
        $visitor = new GenericsVisitor('test',$code);
        $this->traverser->addVisitor($visitor);
        $this->traverser->traverse($ast);
        return $visitor;
    }

    #[WithoutErrorHandler]
    public function testConcreteInstantiationTrait()
    {
        $closure = Foo::new("int");
        $instance = $closure(42);
        $expected_class = 'Foo‹int›';
        $this->assertInstanceOf(\Closure::class,$closure);
        $this->assertInstanceOf($expected_class,$instance);
        $this->assertEquals(42,$instance->x);
    }

    #[WithoutErrorHandler]
    public function testInvalidType()
    {
        $closure = Foo::new("float");
        $this->expectException(\Generics\TypeError::class);
        $closure('abc');
    }

}

#[Generics\T]
class Foo {
    use \Generics\GenericTrait;
    public function __construct(#[Generics\T] public $x)
    {}
}