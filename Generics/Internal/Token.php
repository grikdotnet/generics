<?php declare(strict_types=1);

namespace Generics\Internal;

/**
 * @internal
 */
abstract class Token {
    public function __construct(
        public readonly int $offset,
        public readonly int $length,
    ){}
}