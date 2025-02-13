<?php declare(strict_types=1);

namespace Generics\Internal;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * @internal
 */
final class GenericsVisitor extends NodeVisitorAbstract {

    public ConcreteInstantiationAggregate $instantiations;

    /**
     * @var array<ClassAggregate>
     */
    public array $classes = [];

    public function __construct(
        private readonly string $filename,
        private readonly string $source_code
    ) {
        $this->instantiations = new ConcreteInstantiationAggregate($this->filename);
    }

    /**
     * 2 cases should be processed: classes declared as generic,
     * and classes that use generic type parameters.
     * The task is to find positions of parameter type declarations within source files.
     *
     * @param Node $node
     * @return null
     */
    public function enterNode(Node $node): null
    {
        if ($node instanceof \PhpParser\Node\Stmt\Class_){
            $tokenAggregate = new ClassAggregate($this->filename,$node->name->name);
            $analyzer = new ClassAstAnalyzer($this->source_code, $tokenAggregate);
            $analyzer->do($node);
            if ($tokenAggregate->hasGenerics()){
                $this->classes[] = $tokenAggregate;
            }
        }
        if ($node instanceof \PhpParser\Node\Expr\ArrowFunction) {
            $analyzer = new ArrowAstAnalyzer($this->source_code);
            if ($token = $analyzer->do($node)) {
                $this->instantiations->addToken($token);
            }
        }
        return null;
    }

    public function getConcreteInstantiationAggregate(): ConcreteInstantiationAggregate
    {
        return $this->instantiations;
    }
}
