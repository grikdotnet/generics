<?php declare(strict_types=1);

use Composer\Autoload\ClassLoader;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;
use PHPUnit\Framework\TestCase;

class InstantiationTraitTest extends TestCase
{
    public function setUp(): void
    {
        $composer = current(ClassLoader::getRegisteredLoaders());
        $composer->addClassMap([Foo::class=>__FILE__]);
        new \Generics\Enable();
    }
    #[WithoutErrorHandler]
    public function testConcreteInstantiationTrait()
    {
        $closure = Foo::T("int");
        $this->assertInstanceOf(\Closure::class,$closure);
        $instance = $closure(42);
        $expected_class = 'Foo‹int›';
        $this->assertInstanceOf($expected_class,$instance);
        $this->assertEquals(42,$instance->x);
    }

    #[WithoutErrorHandler]
    public function testInvalidType()
    {
        $this->expectException(\Generics\TypeError::class);
        $this->expectExceptionMessage('Foo‹float›::__construct: Argument #1 ($x) must be of type float, string given');
        Foo::T("float")('abc');
    }
}

#[Generics\T]
class Foo {
    use \Generics\GenericTrait;
    public function __construct(#[Generics\T] public $x)
    {}
}
