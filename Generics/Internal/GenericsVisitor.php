<?php declare(strict_types=1);

namespace Generics\Internal;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * @internal
 */
final class GenericsVisitor extends NodeVisitorAbstract {

    public function __construct(
        private readonly string $source_code,
        private readonly Container $container
    ) {
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
        if (! $node instanceof \PhpParser\Node\Stmt\Class_){
            return null;
        }
        $analyzer = new ClassAstAnalyzer($this->source_code);
        $class_tokens = $analyzer->do($node);
        if ($class_tokens->hasGenerics()){
            $this->container->classes[$analyzer->class_name] = $class_tokens;
        }

        return null;
    }
}
