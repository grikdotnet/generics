<?php declare(strict_types=1);

namespace samples;

readonly class Rate
{
    public function __construct(
        public float $rate,
        public int $currency_code
    ){}
}
