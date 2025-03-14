<?php declare(strict_types=1);

use Generics\Internal\Container;
use PhpParser\ErrorHandler\Collecting;
use PhpParser\Lexer;
use PhpParser\NodeTraverser;
use PhpParser\Parser\Php8;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;
use PHPUnit\Framework\TestCase;

final class InvalidParameterTest extends TestCase
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
        $this->expectExceptionMessage('Invalid generic type Scalar_Int in Foo::__construct($param) line 3');
        $this->traverse($code);

        $code = '<?php
        class Foo{
            public function __construct(int $x, #[\Generics\T(class)] $param){}
        }';
        $this->expectException(\ParseError::class);
        $this->expectExceptionMessage('Invalid generic type Scalar_Int in Foo::__construct($param) line 3');
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