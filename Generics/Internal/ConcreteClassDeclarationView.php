<?php declare(strict_types=1);

namespace Generics\Internal;

readonly class ConcreteClassDeclarationView {

    public function __construct(
        private ClassAggregate $class,
        private string         $source
    ){}

    /**
     *
     * @param ConcreteInstantiationToken $concrete
     * @return string
     */
    public function generateConcreteDeclaration(ConcreteInstantiationToken $concrete): string
    {
        $base_class = $this->class->classname;
        $converted_type = str_replace('\\','⧵',$concrete->parameter_type);

        $code = "class $base_class".'‹'.$converted_type.'› extends '.$base_class.'{';
        foreach ($this->class->getTokens() as $token) {
            if ($token instanceof MethodAggregate) {
                $code .= $this->generateMethod($token, $concrete->parameter_type);
            }
        }
        $code .= '}';
        return $code = $this->strip($code);
    }

    private function generateMethod(MethodAggregate $method, string $concrete_param_type): string
    {
        $headline = substr($this->source, $method->offset, $method->length);
        $parameters = $typed_parameters = [];
        foreach ($method->parameters as $parameter) {
            if ($parameter->type === '') {
                if ($parameter->is_wildcard){
                    $typed_parameters[] = $concrete_param_type. ' $'.$parameter->name;
                } else {
                    $typed_parameters[] = '$'.$parameter->name;
                }
            } else {
                $typed_parameters[] = $parameter->type . ' $'.$parameter->name;
            }
            $parameters[] = '$'.$parameter->name;
        }
        $code = $headline.
            '{try{'.
                'return (fn('.implode(',',$typed_parameters).')=>parent::'.$method->name.'(...func_get_args()))'.
                    '('.implode(',',$parameters).');'.
            '}catch(\TypeError $e){throw \Generics\TypeError::fromTypeError($e);}'.
        '}';
        return $code;
    }

    private function strip(string $sourceCode): string
    {
        $tokens = token_get_all($sourceCode);
        $stripped = '';

        foreach ($tokens as $token) {
            if (is_array($token)) {
                list($id, $text) = $token;

                // Skip comments (T_COMMENT and T_DOC_COMMENT)
                if ($id === T_COMMENT || $id === T_DOC_COMMENT) {
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