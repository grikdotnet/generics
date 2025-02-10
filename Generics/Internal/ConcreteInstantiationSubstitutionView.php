<?php declare(strict_types=1);

namespace Generics\Internal;

/**
 * @internal
 */
class ConcreteInstantiationSubstitutionView {
    const L_ARROW = '‹',
        R_ARROW = '›',
        NS = '⧵';
    public function __construct(
        private readonly ConcreteInstantiationAggregate $aggregate,
        private readonly string                         $source
    ){}

    public function substituteInstantiations(): string
    {
        $token = $this->aggregate->current();
        $code = '';
        $previous_offset = null;
        foreach ($this->aggregate as $token) {
            $type = str_replace('\\',self::NS,$token->concrete_type);
            $code = $token->class_name. self::L_ARROW . $type . self::R_ARROW .
                substr($this->source,$token->offset+$token->length, $previous_offset) . $code;
            $previous_offset = $token->offset;
        }
        $code = substr($this->source,0, $previous_offset) . $code;

        return $code;
    }


}