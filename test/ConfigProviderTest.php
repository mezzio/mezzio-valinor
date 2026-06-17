<?php

declare(strict_types=1);

namespace MezzioTest\Valinor;

use CuyZ\Valinor\Mapper\TreeMapper;
use Laminas\ServiceManager\ServiceManager;
use Mezzio\Valinor\ConfigProvider;
use Mezzio\Valinor\MappingErrorProblemDetailsMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigProvider::class)]
final class ConfigProviderTest extends TestCase
{
    public function testProvidesUsableServiceConfiguration(): void
    {
        $container = new ServiceManager(new ConfigProvider()()['dependencies']);

        self::assertInstanceOf(
            TreeMapper::class,
            $container->get(TreeMapper::class),
        );

        self::assertInstanceOf(
            MappingErrorProblemDetailsMiddleware::class,
            $container->get(MappingErrorProblemDetailsMiddleware::class),
        );
    }
}
