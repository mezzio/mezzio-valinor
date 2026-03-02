<?php

declare(strict_types=1);

namespace MezzioTest\Valinor\Integration\Asset;

final readonly class AddToCart
{
    /**
     * @param non-empty-string $sku
     * @param int<1, max> $quantity
     * @psalm-suppress PossiblyUnusedMethod this constructor is only ever used by valinor
     */
    public function __construct(
        public string $sku,
        public int $quantity,
    ) {
    }
}
