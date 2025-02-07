<?php declare(strict_types=1);

namespace Generics\Internal;

/**
 * @internal
 */
readonly class UnionParameterToken extends ConcreteParameterToken
{
    public function __construct(
        public int      $offset,
        public int      $length,
        public array    $types
    ) {}
}