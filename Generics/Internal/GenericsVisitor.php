<?php declare(strict_types=1);

namespace Generics\Internal;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * @internal
 */
final class GenericsVisitor extends NodeVisitorAbstract {

    private ConcreteInstantiationAggregate $newInstanceAggregate;
    
    public function __construct(
        private readonly string $filename,
        private readonly string $source_code,
        private readonly Container $container
    ) {
        $this->newInstanceAggregate = new ConcreteInstantiationAggregate($this->filename);
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
                $this->container->addClassTokens($this->filename,$analyzer->class_name,$tokenAggregate);
            }
        }
        if ($node instanceof \PhpParser\Node\Expr\ArrowFunction) {
            $analyzer = new ArrowAstAnalyzer($this->source_code, $this->newInstanceAggregate);
            $analyzer->do($node);
        }
        return null;
    }

    public function afterTraverse(array $nodes): null
    {
        if ($this->newInstanceAggregate->hasTokens()){
            $this->container->addNewInstanceTokens($this->filename, $this->newInstanceAggregate);
        }
        return null;
    }
}
