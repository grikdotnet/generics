<?php declare(strict_types=1);

namespace Generics\Internal;

/**
 * @internal
 */
readonly class ArrowAstAnalyzer {

    /**
     * @param string $source_code
     */
    public function __construct(
        private string                         $source_code,
        private ConcreteInstantiationAggregate $aggregate,
    ){
    }

    public function do(\PhpParser\Node\Expr\ArrowFunction $node): void
    {
        //check if class has #[\Generics\New] attribute
        foreach ($node->attrGroups as $group)
            foreach ($group->attrs as $attr)
                if (0 === strcasecmp($attr->name->name, 'Generics\T')) {
                    $attribute = $attr;
                    break 2;
                }
        if (!($attribute ?? false)) {
            return;
        }
        if (! $node->expr instanceof \PhpParser\Node\Expr\New_) {
            return;
        }

        if (!isset($attribute->args[0])) {
            throw new \TypeError('Missing concrete type for the generic instance');
        }
        $parameterNode = $attribute->args[0]->value;

        if ($parameterNode instanceof \PhpParser\Node\Expr\ConstFetch) {
            $concrete_type = substr(
                $this->source_code,
                $s = $parameterNode->getStartFilePos(),
                $parameterNode->getEndFilePos() - $s +1
            );
        } elseif ($parameterNode instanceof \PhpParser\Node\Scalar\String_) {
            $concrete_type = $parameterNode->value;
        } else{
            throw new \TypeError('Invalid parameter type for the generic instance');
        }
        $instance_class = substr($this->source_code,
            $s = $node->expr->class->getStartFilePos(),
            $node->expr->class->getEndFilePos() - $s +1
        );

        $token = new ConcreteInstantiationToken(
            class_name: $instance_class,
            offset: $node->expr->class->getStartFilePos(),
            concrete_type: $concrete_type
        );

        $this->aggregate->addToken($token);
    }
}