<?php declare(strict_types=1);

namespace Generics\Internal;

use PhpParser\Node\Attribute;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\BitwiseOr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;

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
        private readonly ClassAggregate $class
    ){}

    /**
     * @param \PhpParser\Node\Stmt\Class_ $node
     * @return void
     */
    public function do(\PhpParser\Node\Stmt\Class_ $node): void
    {
        $this->class_name = isset($node->namespacedName) ? $node->namespacedName->name : $node->name->name;
        $this->class->setClassname($this->class_name);

        //check if class has #[\Generics\T] attribute
        foreach ($node->attrGroups as $group)
            foreach ($group->attrs as $attr)
                if (0 === strcasecmp($attr->name->name, 'Generics\T')) {
                    if ($node->isFinal()) {
                        throw new \ParseError('A template class can not be final: '.$node->name->name);
                    }
                    $this->class->setIsTemplate();
                    break 2;
                }

        $substitutions = [];
        foreach ($node->getMethods() as $method) {
            $methodAggregate = $this->makeMethodAggregate($method);
            $is_generic = false;
            foreach ($method->attrGroups as $attrGroup) {
                foreach ($attrGroup->attrs as $methodAttribute)
                    if ($methodAttribute->name->name === 'Generics\ReturnT'){
                        $methodAggregate->setWildcardReturn();
                        $is_generic = true;
                    }
            }

            foreach ($method->params as $param) {
                //find parameters with a #[\Generics\T] attribute
                foreach ($param->attrGroups as $attrGroup)
                    foreach ($attrGroup->attrs as $attr)
                        if ($attr->name->name == 'Generics\T') {
                            if ($attr->args === []) {
                                if (!$this->class->isTemplate()) {
                                    $message = 'Missing concrete type of the generic parameter '
                                        . $this->class_name . '::' . $method->name->name . '($' . $param->var->name . ')'
                                        . ' on line ' . $attr->getLine();
                                    throw new \ParseError($message);
                                }
                                $token = $this->wildcardParameter($method->name->name, $param);
                            } else {
                                //this is a concrete generic type parameter, i.e. Foo<int>
                                $token = $this->concreteParameter($method->name->name, $param, $attr);
                            }
                            $methodAggregate->addParameter($token);
                            $is_generic = true;
                            continue 3;
                        }
                $methodAggregate->addParameter(new Parameter(
                    offset: $s = $param->getStartFilePos(),
                    length: $param->var->getEndFilePos() - $s +1,
                    name: $param->var->name,
                    //type will include variadic and reference modifiers
                    type: ($s === $param->var->getStartFilePos())
                        ? ''
                        : trim(substr($this->source_code,$s,$param->var->getStartFilePos()-$s))
                ));
            }

            if ($is_generic) {
                $this->class->addMethodAggregate($methodAggregate);
            }
        }
    }

    private function makeMethodAggregate(ClassMethod $classMethod): MethodAggregate
    {
        if ($classMethod->returnType) {
            $header_end_position = $classMethod->returnType->getEndFilePos();
        } else {
            // there should be some parameters in a method to get parsed here
            $header_end_position = strpos($this->source_code, ')',$classMethod->name->getEndFilePos())+1;
        }
        return new MethodAggregate(
            offset: $s = $classMethod->getStartFilePos(),
            length: $header_end_position - $s,
            name: $classMethod->name->name,
        );
    }

    /**
     * @param string $method_name
     * @param Param $param
     * @return Parameter
     */
    private function wildcardParameter(string $method_name, Param $param): Parameter
    {
        $name = $param->var->name;
        if ($param->type !== null) {
            $message = 'The template parameter should have no type in '.$this->class_name.'::'.$method_name
                .'('.$param->type->name .' $'.$name.') line '. $param->getLine();
            throw new \ParseError($message);
        }
        $parameter = new Parameter(
            offset: $s = $param->var->getStartFilePos(),
            length: $param->var->getEndFilePos() - $s +1,
            name: $name,
            type: '',
            is_wildcard: true
        );
        return $parameter;
    }

    /**
     * @param string $method_name
     * @param Param $param
     * @param Attribute $attr
     * @return Parameter
     * @throws \ParseError
     * @throws \RuntimeException
     */
    private function concreteParameter(string $method_name, Param $param, Attribute $attr): Parameter
    {
        if ($attr->args === []) {
            throw new \RuntimeException('concreteParameter() should not be called for an attribute without a parameter');
        }

        $attributeParamExpr = $attr->args[0]->value;
        try{
            $attribute_parameter = $this->getSource($attributeParamExpr);
        }catch (\TypeError $E){
            throw new \ParseError (
                'Invalid generic type ' . $attributeParamExpr->getType() . ' in '.
                $this->class_name.'::'.$method_name.'($'.$param->var->name.') line '.$attr->getLine()
            );
        }
        if (preg_match('/^\s*((\\\\|\S)+)\s*<(\s*(\\\\|\S)+\s*) *>/i',$attribute_parameter,$matches)) {
            //syntax #[\Generics\T("Foo<Bar>")] $x is used
            $concrete_type = $matches[3];
            $generic_type = $matches[1];
            if ($param->type && 0 !== strcasecmp($param->type->name,$generic_type)) {
                throw new \ParseError (
                    'A parameter type ' . $param->type->name . ' does not match the generic type '.
                    $matches[1] .' in ' .
                    $this->class_name.'::'.$method_name.'('.$param->type->name.' $'.$param->var->name.') on line '
                    .$attr->getLine()
                );
            }
        } elseif ($param->type) {
            $generic_type = $param->type->name;
            $concrete_type = $attribute_parameter;
        } else {
            throw new \ParseError('A non-generic parameter type ('.$attribute_parameter.
                ') should be declared explicitly for '.
                $this->class_name.'::'.$method_name.'($'.$param->var->name.') line '.$attr->getLine())
            ;
        }

        $token = new Parameter(
                offset: $s = $param->getStartFilePos(),
                length: $param->getEndFilePos() - $s +1,
                name: $param->var->name,
                type: $generic_type,
                concrete_type: $concrete_type
            );
        return $token;
    }

    /**
     * @param BitwiseOr $attributeParametersNode
     * @param Param $functionParameterNode
     * @return UnionParameterToken
     */
    private function makeUnionType(BitwiseOr $attributeParametersNode, Param $functionParameterNode) : UnionParameterToken
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

        return new UnionParameterToken(
            offset: $s = $functionParameterNode->getStartFilePos(),
            length: $functionParameterNode->getEndFilePos() - $s +1,
            types: $type_nodes
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