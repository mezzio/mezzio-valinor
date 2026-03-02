<?php

declare(strict_types=1);

namespace Mezzio\Valinor;

use CuyZ\Valinor\Mapper\TreeMapper;
use CuyZ\Valinor\MapperBuilder;

/**
 * Provides auto-merged configuration to be used within Mezzio applications.
 *
 * @link https://docs.laminas.dev/laminas-config-aggregator/config-providers/
 */
final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getDependencies(): array
    {
        return [
            'factories' => [
                MapperBuilder::class => DefaultMapperBuilderFactory::class,
                TreeMapper::class    => TreeMapperFactory::class,
            ],
        ];
    }
}
