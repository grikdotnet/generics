<?php declare(strict_types=1);

namespace grikdotnet\generics\Internal\view;

use grikdotnet\generics\Internal\tokens\ClassAggregate;
use grikdotnet\generics\Internal\tokens\MethodHeaderAggregate;

/**
 * @internal
 */
readonly class ConcreteView {

    const L_ARROW = '‹',
        R_ARROW = '›',
        NS = '⧵';

    private const BUILTIN_TYPES = ['int','float','bool','true','false','null','string','array','callable'];

    /**
     * Generate a string of the concrete type from the template class and a concrete type
     *
     * @param class-string $base_type
     * @param string[] $concrete_types
     * @return class-string
     */
    public static function makeConcreteName(string $base_type, array $concrete_types): string
    {
        $ct = '';
        foreach ($concrete_types as $t) {
            if (!in_array($t,self::BUILTIN_TYPES) && $t !== '' && $t[0] !== '\\') {
                $t = '\\'.$t;
            }
            $ct .= self::L_ARROW.str_replace('\\',self::NS,$t).self::R_ARROW;
        }
        return $base_type.$ct;
    }

    public function __construct(private ClassAggregate $class)
    {}

    /**
     * Generate the class declaration when an instance is created with a trait
     *
     * @param string[] $concrete_types
     * @return string
     */
    public function generateConcreteDeclaration(array $concrete_types): string
    {
        $namespace_clause = '';
        if ($this->class->namespace !== '') {
            $namespace_clause = 'namespace '.$this->class->namespace.';';
        }

        $code = $namespace_clause.'class '.self::makeConcreteName($this->class->classname,$concrete_types)
            .' extends '.$this->class->classname.'{';
        foreach ($this->class->getTokens() as $token) {
            if ($token instanceof MethodHeaderAggregate) {
                $code .= $this->generateMethod($token, $concrete_types);
            }
        }
        $code .= '}';
        return $code = self::strip($code);
    }

    /**
     * Generates a method in the inherited virtual class that is compatible with declaration
     * in the wildcard template class, adding validation of the types for the concrete parameters
     *
     * @param MethodHeaderAggregate $method
     * @param string[] $concrete_param_types
     * @return string
     */
    private function generateMethod(MethodHeaderAggregate $method, array $concrete_param_types): string
    {
        $parameters = $typed_parameters = [];
        foreach ($method->parameters as $parameter) {
            if ($parameter->type === '') {
                if ($parameter->is_wildcard && $concrete_param_types !== []){
                    if ($concrete_param_types) {
                        $type = array_shift($concrete_param_types);
                        if (!in_array($type,self::BUILTIN_TYPES) && $type !== '' && $type[0] !== '\\') {
                            $type = '\\'.$type;
                        }
                        $typed_parameters[] = $type.' $'.$parameter->name;
                    }
                } else {
                    $typed_parameters[] = '$'.$parameter->name;
                }
            } else {
                if ($parameter->concrete_types === []) {
                    $typed_parameters[] = $parameter->type.' $'.$parameter->name;
                } else {
                    $type = self::makeConcreteName($parameter->type,$parameter->concrete_types);
                    $typed_parameters[] = str_replace('\\',self::NS,$type).' $'.$parameter->name;
                }
            }
            $parameters[] = '$'.$parameter->name;
        }
        $return = $method->void ? '' : 'return ';
        $code = $method->headline.
            '{try{'.
            $return.'(fn('.implode(',',$typed_parameters).')=>parent::'.$method->name.'(...func_get_args()))'.
                    '('.implode(',',$parameters).');'.
            '}catch(\TypeError $e){throw \grikdotnet\generics\TypeError::fromTypeError($e);}'.
        '}';
        return $code;
    }

    /**
     * Remove some of the redudant whitespaces from the class declaration
     *
     * @param string $sourceCode
     * @return string
     */
    public static function strip(string $sourceCode): string
    {
        $tokens = token_get_all('<?php '.$sourceCode);
        $stripped = '';

        foreach ($tokens as $token) {
            if (is_array($token)) {
                list($id, $text) = $token;

                // Skip comments (T_COMMENT and T_DOC_COMMENT)
                if ($id === T_OPEN_TAG || $id === T_COMMENT || $id === T_DOC_COMMENT) {
                    continue;
                }

                // Keep everything else (including whitespace tokens)
                $stripped .= $text;
            } else {
                // Non-array tokens are simple characters like braces, semicolons, etc.
                $stripped .= $token;
            }
        }

        // Remove extra whitespace (optional, for further minification)
        return preg_replace('/\s+/', ' ', $stripped);
    }
}