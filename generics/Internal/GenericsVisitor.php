<?php declare(strict_types=1);

namespace grikdotnet\generics\Internal;

use grikdotnet\generics\Internal\model\ArrowAstAnalyzer;
use grikdotnet\generics\Internal\model\ClassAstAnalyzer;
use grikdotnet\generics\Internal\tokens\ClassAggregate;
use grikdotnet\generics\Internal\tokens\ConcreteInstantiationToken;
use grikdotnet\generics\Internal\tokens\FileAggregate;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * an implementation of a Visitor pattern as designed in the Nikic Parser
 * @internal
 */
final class GenericsVisitor extends NodeVisitorAbstract {

    /**
     * @var ConcreteInstantiationToken[]
     */
    private array $instantiations = [];

    /**
     * @var ClassAggregate[]
     */
    private array $classes = [];

    public function __construct(
        private readonly string $path,
        private readonly string $source_code
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
        if ($node instanceof \PhpParser\Node\Stmt\Class_) {
            $analyzer = new ClassAstAnalyzer($this->source_code);
            $classAggregate = $analyzer->do($node);
            if ($classAggregate->hasGenerics()) {
                $this->classes[$classAggregate->classname] = $classAggregate;
            }
        }
        if ($node instanceof \PhpParser\Node\Expr\ArrowFunction) {
            if ($token = ArrowAstAnalyzer::do($this->source_code,$node)) {
                $this->instantiations[$token->offset] = $token;
            }
        }
        return null;
    }

    public function getFileTokens(): FileAggregate
    {
        return new FileAggregate($this->path,$this->classes,$this->instantiations);
    }
}
