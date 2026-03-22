<?php

declare(strict_types=1);

namespace Mezzio\Valinor;

use CuyZ\Valinor\Mapper\TreeMapper;
use CuyZ\Valinor\MapperBuilder;
use Psr\Container\ContainerInterface;

/**
 * @internal you shouldn't reference this class directly: either you rely on the {@see TreeMapper} service provided
 * by this package, or you should ship your own service definition.
 *
 * @psalm-internal \MezzioTest
 * @psalm-internal \Mezzio
 */
final class TreeMapperFactory
{
    public function __invoke(ContainerInterface $container): TreeMapper
    {
        return $container
            ->get(MapperBuilder::class)
            ->mapper();
    }
}
