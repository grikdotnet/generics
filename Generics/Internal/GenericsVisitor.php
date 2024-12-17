<?php declare(strict_types=1);

namespace Generics\Internal;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * @internal
 */
final class GenericsVisitor extends NodeVisitorAbstract {

    public function __construct(
        private readonly string $filename,
        private readonly string $source_code,
        private readonly Container $container
    ) {}

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
            $aggregate = new ClassTokenAggregate($this->filename,$node->name->name);
            $analyzer = new ClassAstAnalyzer($this->source_code, $aggregate);
            $analyzer->do($node);
            if ($aggregate->hasGenerics()){
                $this->container->addClassTokens($this->filename,$analyzer->class_name,$aggregate);
            }
        }
        if ($node instanceof \PhpParser\Node\Expr\ArrowFunction) {
            $analyzer = new ArrowAstAnalyzer($this->source_code);
            $tokens = $analyzer->do($node);
            if ($tokens->hasTemplateInstance()) {
                $this->container->instantiations[] = $tokens;
            }
        }
        return null;
    }
}
