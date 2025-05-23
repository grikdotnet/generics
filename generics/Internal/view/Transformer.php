<?php declare(strict_types=1);

namespace grikdotnet\generics\Internal\view;

use grikdotnet\generics\Internal\tokens\FileAggregate;
use grikdotnet\generics\Internal\tokens\Parameter;
use grikdotnet\generics\Internal\tokens\Token;

/**
 * Applies tokens found by the parser to the PHP code.
 * This is augmentation of code of autoloaded classes.
 * The PHP code is read from files that were found with Composer for the class name.
 *
 * @internal
 */
readonly class Transformer {

    /**
     * This method is called from the autoloader
     *
     * @param string $source
     * @param FileAggregate $fileAggregate
     * @return string
     */
    public static function augment(string $source, FileAggregate $fileAggregate): string
    {
        $tokens = self::prepareTokens($fileAggregate);
        foreach ($tokens as $token) {
            $source = substr($source,0,$token->offset)
                . ConcreteView::makeConcreteName($token->type,$token->concrete_types)
                . ($token instanceof Parameter ? ' $'.$token->name : '')
                . substr($source,$token->offset+$token->length);
        }
        return $source;
    }

    /**
     * Find tokens for concrete generic parameters,
     * e.g. function foo(#[\Generics\T(Bar)] $param) ...,
     * and arrow functions with concrete instantiations,
     * and sort them according to positions in file in reverse order
     *
     * @param FileAggregate $fileAggregate
     * @return Token[]
     */
    private static function prepareTokens(FileAggregate $fileAggregate): array
    {
        $tokens = [];
        foreach ($fileAggregate->classAggregates as $classAggregate) {
            foreach ($classAggregate->getTokens() as $methodAggregate) {
                foreach ($methodAggregate->parameters as $parameter) {
                    if ($parameter->concrete_types !== '') {
                        $tokens[$parameter->offset] = $parameter;
                    }
                }
            }
        }
        foreach ($fileAggregate->instantiations as $instantiation) {
            $tokens[$instantiation->offset] = $instantiation;
        }
        krsort($tokens, \SORT_NUMERIC);
        return $tokens;
    }
}
