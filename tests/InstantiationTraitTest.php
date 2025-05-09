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
        $instance = new (Foo::T("int","float"))(42,1.32);
        $this->assertInstanceOf(Foo‹int›‹float›::class,$instance);

        $this->assertEquals(42,$instance->x);
    }

    #[WithoutErrorHandler]
    public function testInvalidType()
    {
        $this->expectException(\Generics\TypeError::class);
        $this->expectExceptionMessage('Foo‹ABC›‹int›::__construct: Argument #1 ($x) must be of type ABC, string given');
        new (Foo::T("ABC","int"))('abc',4);
    }
}

#[Generics\T]
class Foo {
    use \Generics\GenericTrait;
    public function __construct(#[Generics\T] public $x, #[Generics\T] $y)
    {}
}
