<?php declare(strict_types=1);
if (!class_exists(ParserTestBase::class, false)) {
    include 'ParserTestBase.php';
}

use Generics\Internal\Container;
use PhpParser\ErrorHandler\Collecting;
use PhpParser\Lexer;
use PhpParser\NodeTraverser;
use PhpParser\Parser\Php8;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;
use PHPUnit\Framework\TestCase;

final class InvalidParameterTest extends ParserTestBase
{
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

    public function testGenericParameterWithType(): void
    {
        $code = '<?php
        class Foo{
            public function __construct(int $x, #[\Generics\T("\Foo<int>")] int $param){}
        }';
        $this->expectException(\ParseError::class);
        $this->traverse($code);
    }

    #[WithoutErrorHandler]
    public function testTemplateParameterInNonTemplateClass(): void
    {
        $code = '<?php
        class Foo{
            public function __construct(int $x, #[\Generics\T] $param){}
        }';
        $this->expectException(\ParseError::class);
        $this->expectExceptionMessage('Missing concrete type of the generic parameter Foo::__construct($param) on line 3');
        $this->traverse($code);
    }

    public function testInvalidGenericType(): void
    {
        $code = '<?php
        class Foo{
            public function __construct(int $x, #[\Generics\T(42)] $param){}
        }';
        $this->expectException(\ParseError::class);
        $this->expectExceptionMessage('Invalid concrete type Scalar_Int in Foo::__construct($param) line 3');
        $this->traverse($code);
    }

    public function testTypeError(): void
    {
        $code = '<?php
        class Foo{
            public function __construct(int $x, #[\Generics\T(class)] $param){}
        }';
        $this->expectException(\TypeError::class);
        $this->traverse($code);
    }

    public function testEmptyGenericParameterType(): void
    {
        $code = '<?php
        class Foo{
            public function __construct(int $x, #[\Generics\T()] $param){}
        }';
        $this->expectException(\ParseError::class);
        $this->expectExceptionMessage('Missing concrete type of the generic parameter Foo::__construct($param) on line 3');
        $this->traverse($code);
    }

    public function testFinalTemplateClass(): void
    {
        $code = '<?php
        #[\Generics\T]
        final class Foo{
            public function __construct(int $x, #[\Generics\T()] $param){}
        }';
        $this->expectException(\ParseError::class);
        $this->expectExceptionMessage('A template class can not be final');
        $this->traverse($code);
    }

}