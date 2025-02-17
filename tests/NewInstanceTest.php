<?php declare(strict_types=1);

use Generics\Internal\GenericsVisitor;
use PhpParser\ErrorHandler\Collecting;
use PhpParser\Lexer;
use PhpParser\NodeTraverser;
use PhpParser\Parser\Php8;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;
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

    private function traverse(string $code): GenericsVisitor {
        $ast = $this->parser->parse($code, new Collecting);
        $visitor = new GenericsVisitor('test',$code);
        $this->traverser->addVisitor($visitor);
        $this->traverser->traverse($ast);
        return $visitor;
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
            offset: 129,
            length: 8,
            class_name: "Acme\Foo",
            concrete_type: "int"
        ));
        $expected->addToken(new \Generics\Internal\ConcreteInstantiationToken(
            offset: 194,
            length: 9,
            class_name: "\Acme\Bar",
            concrete_type: "MyClass"
        ));

        $visitor = $this->traverse($code);
        self::assertEquals($expected, $visitor->instantiations);
    }

    #[WithoutErrorHandler]
    public function testNamespacedInstantiation()
    {
        $code = '<?php namespace ACME;
            $c = (#[Generics\T(\Acme\Bar)] fn() => new Foo($x))(); ';
        $expected = new \Generics\Internal\ConcreteInstantiationAggregate('test');

        $expected->addToken(new \Generics\Internal\ConcreteInstantiationToken(
            offset: 77,
            length: 3,
            class_name: "Foo",
            concrete_type: "\Acme\Bar"
        ));

        $visitor = $this->traverse($code);
        self::assertEquals($expected, $visitor->instantiations);
    }

    #[WithoutErrorHandler]
    public function testConcreteInstantiationSubstitution()
    {
        $code = ' $c = (#[Generics\T(\Acme\Bar)] fn() => new Foo($x))(); ';

        $aggregate = new \Generics\Internal\ConcreteInstantiationAggregate('test');
        $aggregate->addToken(new \Generics\Internal\ConcreteInstantiationToken(
            offset: 44,
            length: 3,
            class_name: "Foo",
            concrete_type: "\Acme\Bar",
        ));

        $expected = ' $c = (#[Generics\T(\Acme\Bar)] fn() => new Foo‹⧵Acme⧵Bar›($x))(); ';
        $view = new \Generics\Internal\ConcreteInstantiationSubstitutionView($aggregate,$code);
        $new_code = $view->substituteInstantiations();
        self::assertEquals($expected, $new_code);
    }

}
