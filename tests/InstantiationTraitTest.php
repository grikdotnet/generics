<?php declare(strict_types=1);

use PhpParser\Lexer;
use PhpParser\NodeTraverser;
use PhpParser\Parser\Php8;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;
use PHPUnit\Framework\TestCase;

class InstantiationTraitTest extends TestCase
{
    public function __construct(string $name)
    {
        parent::__construct($name);
        new \Generics\Enable();
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
            $this->expectExceptionMessage('Foo‹float›::__construct: Argument #1 ($x) must be of type float, string given');
            $closure('abc');
    }

}

#[Generics\T]
class Foo {
    use \Generics\GenericTrait;
    public function __construct(#[Generics\T] public $x)
    {}
}