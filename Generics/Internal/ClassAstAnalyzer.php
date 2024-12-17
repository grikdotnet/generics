<?php declare(strict_types=1);

namespace Generics\Internal;

use PhpParser\Node\Attribute;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\BitwiseOr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\String_;

/**
 * @internal
 */
final class ClassAstAnalyzer
{
    public readonly string $class_name;

    private array $restricted_names = ['__halt_compiler'=>0,'abstract'=>0,'and'=>0,'array'=>0,'as'=>0,'break'=>0,'callable'=>0,'case'=>0,'catch'=>0,'class'=>0,'clone'=>0,'const'=>0,'continue'=>0,'declare'=>0,'default'=>0,'die'=>0,'do'=>0,'echo'=>0,'else'=>0,'elseif'=>0,'empty'=>0,'enddeclare'=>0,'endfor'=>0,'endforeach'=>0,'endif'=>0,'endswitch'=>0,'endwhile'=>0,'enum'=>0,'eval'=>0,'exit'=>0,'extends'=>0,'final'=>0,'finally'=>0,'for'=>0,'foreach'=>0,'fn'=>0,'function'=>0,'global'=>0,'goto'=>0,'if'=>0,'implements'=>0,'include'=>0,'include_once'=>0,'instanceof'=>0,'insteadof'=>0,'interface'=>0,'match'=>0,'isset'=>0,'list'=>0,'namespace'=>0,'never'=>0,'new'=>0,'object'=>0,'or'=>0,'print'=>0,'private'=>0,'protected'=>0,'public'=>0,'require'=>0,'require_once'=>0,'return'=>0,'switch'=>0,'throw'=>0,'trait'=>0,'try'=>0,'unset'=>0,'use'=>0,'var'=>0,'void'=>0,'while'=>0,'xor'=>0,'yield'=>0,'self'=>0,'parent'=>0,'static'=>0,'__class__'=>0,'__dir__'=>0,'__file__'=>0,'__function__'=>0,'__line__'=>0,'__method__'=>0,'__namespace__'=>0,'__trait__'=>0];
    private array $builtin = ['int','float','bool','true','false','null','string','array','callable'];

    public function __construct(
        private readonly string $source_code,
        private readonly ClassTokenAggregate $aggregate
    ){}

    /**
     * @param \PhpParser\Node\Stmt\Class_ $node
     * @return void
     */
    public function do(\PhpParser\Node\Stmt\Class_ $node): void
    {
        $this->class_name = isset($node->namespacedName) ? $node->namespacedName->name : $node->name->name;


        //check if class has #[\Generics\T] attribute
        foreach ($node->attrGroups as $group)
            foreach ($group->attrs as $attr)
                if (0 === strcasecmp($attr->name->name, 'Generics\T')) {
                    $this->aggregate->setIsTemplate();
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
                                if (!$this->aggregate->isTemplate()) {
                                    $message = 'A template parameter should not be used in a non-template class '
                                        .$this->class_name.'::'.$method->name->name.'($'.$param->var->name.')'
                                        .' line '.$attr->getLine();
                                    throw new \ParseError($message);
                                }
                                $token = $this->templateParameter($method->name->name, $param);
                                $this->aggregate->addToken($token);
                                break 3;
                            case 'Generics\ParameterType':
                                $token = $this->genericTypeParameter($method->name->name, $param, $attr);
                                $this->aggregate->addToken($token);
                                break 3;
                        }
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
            $message = 'The template parameter should have no type in '.$this->class_name.'::'.$method_name
                .'('.$param->type->name .' $'.$name.') line '. $param->getLine();
            throw new \ParseError($message);
        }
        $token = new Token(
            offset: $param->getStartFilePos(),
            parameter_name: $param->var->name,
            parameter_type: null,
            type_type: TypeType::Template
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
            throw new \ParseError ('Missing type of the generic parameter '.
                $this->class_name.'::'.$method_name.'($'.$param->var->name.') on line '.$attr->getLine());
        }
        if ($param->type !== null) {
            $message = 'The generic parameter should have no type in '.$this->class_name.'::'.$method_name
                .'('.$param->type->name .' $'.$param->var->name.') line '.$param->getLine();
            throw new \ParseError($message);
        }
        $attributeParamExpr = $attr->args[0]->value;

        try{
            if ($attributeParamExpr instanceof BitwiseOr) {
                return $this->makeUnionType($attributeParamExpr, $param);
            }
            $param_type = $this->getSource($attributeParamExpr);
        }catch (\TypeError $E){
            throw new \ParseError (
                'Invalid generic type ' . $attributeParamExpr->getType() . ' in '.
                $this->class_name.'::'.$method_name.'($'.$param->var->name.') line '.$attr->getLine()
            );
        }

        return new Token(
            offset: $param->var->getStartFilePos(),
            parameter_name: $param->var->name,
            parameter_type: $param_type
        );
    }

    /**
     * @param BitwiseOr $attributeParametersNode
     * @param Param $functionParameterNode
     * @return UnionToken
     */
    private function makeUnionType(BitwiseOr $attributeParametersNode, Param $functionParameterNode) : UnionToken
    {
        $type_nodes = [];
        $node = $attributeParametersNode;
        while (true) {
            $type_nodes[] = $this->getSource($node->right);

            if ($node->left instanceof BitwiseOr) {
                $node = $node->left;
                continue;
            }
            $type_nodes[] = $this->getSource($node->left);
            break;
        }

        return new UnionToken(
            $functionParameterNode->getStartFilePos(),
            $functionParameterNode->var->name,
            $type_nodes
        );
    }

    /**
     * Fetch the type from the source code, cause php-parser removes leading \ from the class name
     *
     * @param Expr $expr
     * @return string
     */
    private function getSource(Expr $expr): string
    {
        $param_type = match (true) {
            $expr instanceof String_ => $expr->value,
            $expr instanceof ConstFetch =>
                substr($this->source_code, $s = $expr->getStartFilePos(), $expr->getEndFilePos() - $s + 1),
            $expr instanceof ClassConstFetch =>
                substr($this->source_code, $s = $expr->getStartFilePos(), $expr->getEndFilePos() - $s - 6),
            default => throw new \TypeError()
        };
        if (!$param_type || is_countable($param_type) || isset($this->restricted_names[strtolower($param_type)])) {
            throw new \TypeError();
        }
        return $param_type;
    }

}