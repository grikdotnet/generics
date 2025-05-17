<?php declare(strict_types=1);

namespace grikdotnet\generics\Internal\model;

use grikdotnet\generics\Internal\tokens\ClassAggregate;
use grikdotnet\generics\Internal\tokens\MethodHeaderAggregate;
use grikdotnet\generics\Internal\tokens\Parameter;
use grikdotnet\generics\Internal\view\ConcreteView;
use PhpParser\Node\{Attribute,
    Expr,
    Expr\ClassConstFetch,
    Expr\ConstFetch,
    Name,
    Param,
    Scalar\String_,
    Stmt\Class_,
    Stmt\ClassMethod};

/**
 * @internal
 */
final class ClassAstAnalyzer
{
    public readonly string $fqcn;

    private array $restricted_names = ['__halt_compiler'=>0,'abstract'=>0,'and'=>0,'array'=>0,'as'=>0,'break'=>0,'callable'=>0,'case'=>0,'catch'=>0,'class'=>0,'clone'=>0,'const'=>0,'continue'=>0,'declare'=>0,'default'=>0,'die'=>0,'do'=>0,'echo'=>0,'else'=>0,'elseif'=>0,'empty'=>0,'enddeclare'=>0,'endfor'=>0,'endforeach'=>0,'endif'=>0,'endswitch'=>0,'endwhile'=>0,'enum'=>0,'eval'=>0,'exit'=>0,'extends'=>0,'final'=>0,'finally'=>0,'for'=>0,'foreach'=>0,'fn'=>0,'function'=>0,'global'=>0,'goto'=>0,'if'=>0,'implements'=>0,'include'=>0,'include_once'=>0,'instanceof'=>0,'insteadof'=>0,'interface'=>0,'match'=>0,'isset'=>0,'list'=>0,'namespace'=>0,'never'=>0,'new'=>0,'object'=>0,'or'=>0,'print'=>0,'private'=>0,'protected'=>0,'public'=>0,'require'=>0,'require_once'=>0,'return'=>0,'switch'=>0,'throw'=>0,'trait'=>0,'try'=>0,'unset'=>0,'use'=>0,'var'=>0,'void'=>0,'while'=>0,'xor'=>0,'yield'=>0,'self'=>0,'parent'=>0,'static'=>0,'__class__'=>0,'__dir__'=>0,'__file__'=>0,'__function__'=>0,'__line__'=>0,'__method__'=>0,'__namespace__'=>0,'__trait__'=>0];
    public function __construct(
        private readonly string $source_code,
    ){}

    public function do(Class_ $node): ClassAggregate
    {
        if (isset($node->namespacedName) && $node->namespacedName->name !== $node->name->name) {
            $namespace = substr($node->namespacedName->name,0,strrpos($node->namespacedName->name,'\\'));
        }
        $class = new ClassAggregate($node->name->name, $namespace??'');
        $this->fqcn = isset($node->namespacedName) ? $node->namespacedName->name : $node->name->name;

        //check if the class has a #[\Generics\T] attribute
        foreach ($node->attrGroups as $group)
            foreach ($group->attrs as $attr)
                if (0 === strcasecmp($attr->name->name, 'Generics\T')) {
                    if ($node->isFinal()) {
                        throw new \ParseError('A template class can not be final: '.$node->name->name);
                    }
                    $class->setIsTemplate();
                    break 2;
                }

        $substitutions = [];
        foreach ($node->getMethods() as $method) {
            if (!($methodAggregate = $this->makeMethodAggregate($method))) {
                continue;
            }
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
                                if (!$class->isTemplate()) {
                                    $message = 'Missing concrete type of the generic parameter '
                                        . $this->fqcn . '::' . $method->name->name . '($' . $param->var->name . ')'
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
                $class->addMethodAggregate($methodAggregate);
            }
        }
        return $class;
    }

    private function makeMethodAggregate(ClassMethod $classMethod): MethodHeaderAggregate| false
    {
        $s = $classMethod->getStartFilePos();
        $void = false;
        if ($classMethod->returnType) {
            $header_end_position = $classMethod->returnType->getEndFilePos()+1;
            $headline = substr($this->source_code, $s, $header_end_position - $s);
            if ('void' == $classMethod->returnType) {
                $void = true;
            }
        } elseif ($classMethod->params !== []) {
            $header_end_position = end($classMethod->params)->getEndFilePos()+1;
            $headline = substr($this->source_code, $s, $header_end_position - $s).')';
        } else {
            return false;
        }
        $headline = ConcreteView::strip($headline);
        return new MethodHeaderAggregate(
            offset: $s,
            length: $header_end_position - $s,
            name: $classMethod->name->name,
            headline: $headline,
            void: $void,
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
            $message = 'The template parameter should have no type in '.$this->fqcn.'::'.$method_name
                .'('.$param->type->name .' $'.$name.') line '. $param->getLine();
            throw new \ParseError($message);
        }
        return new Parameter(
            offset: $s = $param->var->getStartFilePos(),
            length: $param->var->getEndFilePos() - $s +1,
            name: $name,
            type: '',
            is_wildcard: true
        );
    }

    /**
     * Create a token for a concrete parameter
     *
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
            throw new \RuntimeException('Parse error: the concrete type attribute has no parameter');
        }

        $wildcard_type = false;
        if ($param->type !== null ) {
            if (!$param->type instanceof Name) {
                throw new \ParseError('Invalid wildcard type for a concrete parameter in ' .
                    $this->fqcn . '::' . $method_name . '($' . $param->var->name . ') line ' . $attr->getLine()
                );
            }
            $wildcard_type = ($param->type instanceof Name\FullyQualified ? '\\' : '') . $param->type->name;
        }

        //There are seversal possible syntaxes to define concrete types to define a concrete type
        // for a wildcard parameter. The first is #[\Generics\T("int", "float")] Foo $param
        if (isset($attr->args[1])) {
            //more than one parameter provided, so it is the first syntax with several concrete types
            //ensure the generic type is provided
            if (!$wildcard_type) {
                throw new \ParseError('Missing wildcard type for a concrete parameter in '.
                    $this->fqcn.'::'.$method_name.'($'.$param->var->name.') line '.$attr->getLine()
                );
            }
            $concrete_types = array_map(fn($a)=>$this->getSource($a->value),$attr->args);
        } else {
            // the second syntax is eitehr #[\Generics\T("Foo<int><float>")] Foo $param
            $attributeParamExpr = $attr->args[0]->value;
            try {
                $concrete_type_declaration = $this->getSource($attributeParamExpr);
            } catch (\TypeError $E) {
                throw new \ParseError (
                    'Invalid concrete type ' . $attributeParamExpr->getType() . ' in '.
                    $this->fqcn.'::'.$method_name.'($'.$param->var->name.') line '.$attr->getLine()
                );
            }
            if (preg_match('/^\s*([^<>\s]+)\s*<\s*[^<>\s]+\s*>/',$concrete_type_declaration,$matches)) {
                if ($wildcard_type && 0 !== strcasecmp($param->type->name,$wildcard_type)) {
                    throw new \ParseError (
                        'The parameter ' . $param->type->name . ' type does not match the wildcard type '.
                        $wildcard_type .' in ' .
                        $this->fqcn.'::'.$method_name.'('.$param->type->name.' $'.$param->var->name.') on line '
                        .$attr->getLine()
                    );
                }
                if (!$wildcard_type) {
                    $wildcard_type = $matches[1];
                }
                preg_match_all('/\s*<\s*([^<>\s]+)\s*>\s*/', $concrete_type_declaration, $matches);
                $concrete_types = $matches[1];
            } elseif($wildcard_type) {
                // it is a syntax #[\Generics\T("float")] Foo $param
                $concrete_types = [$concrete_type_declaration];
            } else {
                throw new \ParseError (
                    'Invalid concrete type ' . $attributeParamExpr->getType() . ' in '.
                    $this->fqcn.'::'.$method_name.'($'.$param->var->name.') line '.$attr->getLine()
                );
            }
        }

        $token = new Parameter(
            offset: $s = $param->getStartFilePos(),
            length: $param->getEndFilePos() - $s +1,
            name: $param->var->name,
            type: $wildcard_type,
            concrete_types: $concrete_types
            );
        return $token;
    }

    private function extractConcreteParameterParts(string $type): array
    {
        $pattern = '/^([^<>]+)(?:\s*<\s*([^<>]*)\s*>\s*)+/';

        if (preg_match($pattern, $type, $matches)) {
            $wildcard = trim($matches[1]);

            // Extract all values inside angle brackets, allowing for spaces
            preg_match_all('/\s*<\s*([^<>]*)\s*>\s*/', $type, $matches);
            $concrete = array_map('trim', $matches[1]);

            return [$wildcard, $concrete];
        }
        return [];
    }

    /**
     * Fetch the type from the source code, cause php-parser removes leading \ from namespaces
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