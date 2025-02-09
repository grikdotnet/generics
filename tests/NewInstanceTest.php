<?php declare(strict_types=1);

use Generics\Internal\Container;
use Generics\Internal\FileReader;
use Generics\Internal\TypeType;
use PhpParser\ErrorHandler\Collecting;
use PhpParser\Lexer;
use PhpParser\NodeTraverser;
use PhpParser\Parser\Php8;
use PHPUnit\Framework\TestCase;

final class NewInstanceTest extends TestCase
{
    private Php8 $parser;
    private NodeTraverser $traverser;

    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->parser = new Php8(new Lexer);
        $this->traverser = new NodeTraverser;
    }

    private function traverse(string $code): Container {
        $ast = $this->parser->parse($code, new Collecting);
        $file_cache = $this->createStub(FileReader::class);
        $container = new Container($file_cache);
        $this->traverser->addVisitor(new \Generics\Internal\GenericsVisitor('test',$code, $container));
        $this->traverser->traverse($ast);
        return $container;
    }

    public function testNewInstances()
    {
        $code = '<?php 
            new StdClass;
            fn() => new ShouldBeSkipped($x);
            $c = (#[Generics\T("int")] fn() => new Acme\Foo($x))();
            (#[Generics\T(MyClass)] fn() => new \Acme\Bar($y))();
            (fn() => new SkipMe())();
            ';
        $expected = new \Generics\Internal\ConcreteInstantiationAggregate('test');

        $expected->addToken(new \Generics\Internal\ConcreteInstantiationToken(
            class_name: "Acme\Foo",
            offset: 129,
            concrete_type: "int"
        ));
        $expected->addToken(new \Generics\Internal\ConcreteInstantiationToken(
            class_name: "\Acme\Bar",
            offset: 194,
            concrete_type: "MyClass"
        ));

        $container = $this->traverse($code);
        self::assertArrayHasKey('test',$container->instantiations);
        self::assertEquals($expected, $container->instantiations['test']);
    }

    public function testNamespacedInstantiation()
    {
        $code = '<?php namespace ACME;
            $c = (#[Generics\T(\Acme\Bar)] fn() => new Foo($x))(); ';
        $expected = new \Generics\Internal\ConcreteInstantiationAggregate('test');

        $expected->addToken(new \Generics\Internal\ConcreteInstantiationToken(
            class_name: "Foo",
            offset: 77,
            concrete_type: "\Acme\Bar"
        ));

        $container = $this->traverse($code);
        self::assertArrayHasKey('test',$container->instantiations);
        self::assertEquals($expected, $container->instantiations['test']);
    }

}
