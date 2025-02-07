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
        private readonly ClassAggregate $aggregate
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
        foreach ($node->getMethods() as $method) {
            $methodAggregate = null;
            foreach ($method->params as $param)
                //find function parameters with a #[\Generics\T] attribute
                foreach ($param->attrGroups as $attrGroup)
                    foreach ($attrGroup->attrs as $attr)
                        if  ($attr->name->name == 'Generics\T') {
                            $methodAggregate ?? $methodAggregate = $this->makeMethodAggregate($method);
                            if ($attr->args === []) {
                                //this is a wildcard parameter
                                if (!$this->aggregate->isTemplate()) {
                                    $message = 'A template parameter should not be used in a non-template class '
                                        . $this->class_name . '::' . $method->name->name . '($' . $param->var->name . ')'
                                        . ' line ' . $attr->getLine();
                                    throw new \ParseError($message);
                                }
                                $token = $this->wildcardParameter($method->name->name, $param);
                            } elseif($attr->args[0]->value instanceof BitwiseOr) {
                                //this is a union type concrete parameter, i.e. Foo<int>|Bar
                                $token = $this->makeUnionType($attr->args[0]->value, $param);
                            } else {
                                //this is a concrete generic type parameter, i.e. Foo<int>
                                $token = $this->concreteParameter($method->name->name, $param, $attr);
                            }
                            $methodAggregate->addParameterToken($token);
                            break 2;
                        }

            if ($methodAggregate) {
                $this->aggregate->addMethodAggregate($methodAggregate);
            }
        }
    }

    private function makeMethodAggregate(ClassMethod $classMethod): MethodAggregate
    {
        if ($classMethod->returnType) {
            $header_end_position = $classMethod->returnType->getEndFilePos();
        } else {
            // there should be some parameters in a method to get parsed here
            $e = end($classMethod->params)->getEndFilePos();
            $header_end_position = strpos($this->source_code, ')',$e);
        }
        return new MethodAggregate(
            name: $classMethod->name->name,
            offset: $s = $classMethod->getStartFilePos(),
            length: $header_end_position - $s +1,
            parameters_offset: $classMethod->params[0]->getStartFilePos()
        );
    }

    /**
     * @param string $method_name
     * @param Param $param
     * @return WildcardParameterToken
     */
    private function wildcardParameter(string $method_name, Param $param): WildcardParameterToken
    {
        $name = $param->var->name;
        if ($param->type !== null) {
            $message = 'The template parameter should have no type in '.$this->class_name.'::'.$method_name
                .'('.$param->type->name .' $'.$name.') line '. $param->getLine();
            throw new \ParseError($message);
        }
        $token = new WildcardParameterToken(
            offset: $s = $param->var->getStartFilePos(),
            length: $param->var->getEndFilePos() - $s +1
        );
        return $token;
    }

    private function concreteParameter(string $method_name, Param $param, Attribute $attr): ConcreteParameterToken
    {
        if ($attr->args === []) {
            throw new \Exception('Something is wrong, concreteParameter() should not be called when an attribute does not have a parameter');
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
        if (preg_match('/^ *((\\\\|\w)+) *<( *(\\\\|\w)+ *) *>/i',$attribute_parameter,$matches)) {
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
            $generic_type = $param->type;
            $concrete_type = $attribute_parameter;
        } else

        $token = new ConcreteParameterToken(
                offset: $s = $param->var->getStartFilePos(),
                length: $param->var->getEndFilePos() - $s,
                base_type: $matches[1],
                concrete_type: $matches[2]
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