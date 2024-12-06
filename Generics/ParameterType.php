<?php declare(strict_types=1);

namespace Generics;

/**
 * An attribute declaration for IDE autocomplete
 */
#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
class ParameterType {
    function __construct(string $parameterType){}
}
