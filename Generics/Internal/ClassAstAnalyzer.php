<?php declare(strict_types=1);

namespace Generics\Internal;

use PhpParser\Node\Attribute;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\BitwiseOr;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\String_;

/**
 * @internal
 */
final class ClassAstAnalyzer
{
    public readonly string $class_name;

    private array $restricted_names = ['__halt_compiler'=>0,'abstract'=>0,'and'=>0,'array'=>0,'as'=>0,'break'=>0,'callable'=>0,'case'=>0,'catch'=>0,'class'=>0,'clone'=>0,'const'=>0,'continue'=>0,'declare'=>0,'default'=>0,'die'=>0,'do'=>0,'echo'=>0,'else'=>0,'elseif'=>0,'empty'=>0,'enddeclare'=>0,'endfor'=>0,'endforeach'=>0,'endif'=>0,'endswitch'=>0,'endwhile'=>0,'eval'=>0,'exit'=>0,'extends'=>0,'final'=>0,'finally'=>0,'for'=>0,'foreach'=>0,'fn'=>0,'function'=>0,'global'=>0,'goto'=>0,'if'=>0,'implements'=>0,'include'=>0,'include_once'=>0,'instanceof'=>0,'insteadof'=>0,'interface'=>0,'match'=>0,'isset'=>0,'list'=>0,'namespace'=>0,'new'=>0,'object'=>0,'or'=>0,'print'=>0,'private'=>0,'protected'=>0,'public'=>0,'require'=>0,'require_once'=>0,'return'=>0,'switch'=>0,'throw'=>0,'trait'=>0,'try'=>0,'unset'=>0,'use'=>0,'var'=>0,'void'=>0,'while'=>0,'xor'=>0,'yield'=>0,'self'=>0,'parent'=>0,'static'=>0,'__class__'=>0,'__dir__'=>0,'__file__'=>0,'__function__'=>0,'__line__'=>0,'__method__'=>0,'__namespace__'=>0,'__trait__'=>0];

    /**
     * @param \PhpParser\Node\Stmt\Class_ $node
     * @return ClassAggregate
     */
    public function do(\PhpParser\Node\Stmt\Class_ $node): ClassAggregate
    {
        $this->class_name = isset($node->namespacedName) ? $node->namespacedName->name : $node->name->name;
        $aggregate = new ClassAggregate($this->class_name);

        //check if class has #[\Generics\T] attribute
        foreach ($node->attrGroups as $group)
            foreach ($group->attrs as $attr)
                if (0 === strcasecmp($attr->name->name, 'Generics\T')) {
                    $aggregate->setIsTemplate();
                    break 2;
                }

        $substitutions = [];
        foreach ($node->getMethods() as $method)
            foreach ($method->params as $param)
                //find function parameters with a #[\Generics\T] attribute
                foreach ($param->attrGroups as $attrGroup)
                    foreach ($attrGroup->attrs as $attr)
                        switch ($attr->name->name) {
                            case 'Generics\T':
                                $token = $this->templateParameter($method->name->name, $param);
                                $aggregate->addToken($token);
                                break 3;
                            case 'Generics\ParameterType':
                                $token = $this->genericTypeParameter($method->name->name, $param, $attr);
                                break 3;
                        }

        return $aggregate;
    }

    /**
     * @param string $method_name
     * @param Param $param
     * @return Token
     */
    private function templateParameter(string $method_name, Param $param): Token
    {
        $name = $param->var->name;
        if ($param->type !== null) {
            $message = 'A template parameter '.$this->class_name.'::'.$method_name
                .'('.$param->type->name .' '.$name.') should have no type';
            throw new \TypeError($message);
        }
        $token = new Token(
            type_type: TypeType::Template,
            offset: $param->getStartFilePos(),
            parameter_name: $param->var->name,
            parameter_type: null
        );
        return $token;
    }

    /**
     * @param string $method_name
     * @param Param $param
     * @param Attribute $attr
     * @return Token
     */
    private function genericTypeParameter(string $method_name, Param $param, Attribute $attr): Token
    {
        if (!isset($attr->args[0])) {
            throw new \ParseError ('Missing generic parameter type for '.
                $this->class_name.'::'.$method_name.'('.$param->var->name.')');
        }
        $attributeParameterExpr = $attr->args[0]->value;

        if ($attributeParameterExpr instanceof ConstFetch) {
            $name = $attributeParameterExpr->name->name;
            //class name
            if (is_countable($name) || isset($this->restricted_names[strtolower($name)])) {
                throw new \ParseError ('Invalid generic parameter type "'.$name.'"');
            }
            if ($attributeParameterExpr->name instanceof FullyQualified) {
                $name = '\\' . $name;
            }
            return new Token(
                type_type: TypeType::UserDefined,
                offset: $param->getStartFilePos(),
                parameter_name: $param->var->name,
                parameter_type: $name
            );
        }

        if ($attributeParameterExpr instanceof String_) {
            return new Token(
                type_type: TypeType::UserDefined,
                offset: $param->getStartFilePos(),
                parameter_name: $param->var->name,
                parameter_type: $attributeParameterExpr->value
            );
        }

        if ($attributeParameterExpr instanceof BitwiseOr) {
            return $this->makeUnionType($param, $attributeParameterExpr);
        }

        throw new \ParseError (
            'Invalid generic type '.$attributeParameterExpr->getType() .': '.
            substr(
                $this->source_code,
                $s=$attributeParameterExpr->getStartFilePos(),
                $attributeParameterExpr->getEndFilePos()-$s
            )
        );

    }

    private function userDefinedType(string $param_name, ConstFetch $expected_type)
    {

    }

    /** @TODO rewrite the strategy and make UnionType return a composite */
    private function makeUnionType(BitwiseOr $node, ?array &$values = []) : string
    {
        if (isset($node->left->value) ) {
            $values[] = $this->typeStrategy($node->left);
        } elseif ( $node->left instanceof BitwiseOr) {
            self::makeUnionType($node->left, $values);
        } else {
            throw new \ParseError ('Invalid generic type '.$node->getType());
        }

        $values[] = $this->typeStrategy($node->right);

        return '';
    }

}