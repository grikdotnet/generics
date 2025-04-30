<?php declare(strict_types=1);

namespace Generics\Internal\model;

use Generics\Internal\tokens\ConcreteInstantiationToken;

/**
 * @internal
 */
class ArrowAstAnalyzer {

    public static function do(string $source_code, \PhpParser\Node\Expr\ArrowFunction $node): ?ConcreteInstantiationToken
    {
        //check if class has #[\Generics\New] attribute
        foreach ($node->attrGroups as $group)
            foreach ($group->attrs as $attr)
                if (0 === strcasecmp($attr->name->name, 'Generics\T')) {
                    $attribute = $attr;
                    break 2;
                }
        if (!isset($attribute)) {
            return null;
        }
        if (! $node->expr instanceof \PhpParser\Node\Expr\New_) {
            return null;
        }

        if (!isset($attribute->args[0])) {
            throw new \TypeError('Missing concrete type for the generic instance');
        }
        $parameterNode = $attribute->args[0]->value;

        if ($parameterNode instanceof \PhpParser\Node\Expr\ConstFetch) {
            $concrete_type = substr(
                $source_code,
                $s = $parameterNode->getStartFilePos(),
                $parameterNode->getEndFilePos() - $s +1
            );
        } elseif ($parameterNode instanceof \PhpParser\Node\Scalar\String_) {
            $concrete_type = $parameterNode->value;
        } else{
            throw new \TypeError('Invalid parameter type for the generic instance');
        }
        $instance_class = substr($source_code,
            $s = $node->expr->class->getStartFilePos(),
            $node->expr->class->getEndFilePos() - $s +1
        );

        return new ConcreteInstantiationToken(
            offset: $node->expr->class->getStartFilePos(),
            length: strlen($instance_class),
            type: $instance_class,
            concrete_type: $concrete_type
        );
    }
}