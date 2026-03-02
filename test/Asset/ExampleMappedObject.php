<?php

declare(strict_types=1);

namespace MezzioTest\Valinor\Asset;

final readonly class ExampleMappedObject
{
    /**
     * @psalm-suppress PossiblyUnusedMethod this constructor is only ever used by valinor
     */
    public function __construct(
        public int $field1,
        public string $field2,
    ) {
    }
}
