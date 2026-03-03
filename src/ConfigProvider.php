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
    /**
     * @return array{
     *     dependencies: array{
     *         factories: array<class-string, class-string>
     *     },
     *     ...
     * }
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                'factories' => [
                    MapperBuilder::class => DefaultMapperBuilderFactory::class,
                    TreeMapper::class    => TreeMapperFactory::class,
                ],
            ],
        ];
    }
}
