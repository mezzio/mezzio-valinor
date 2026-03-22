<?php

declare(strict_types=1);

namespace MezzioTest\Valinor;

use CuyZ\Valinor\Mapper\TreeMapper;
use Laminas\ServiceManager\ServiceManager;
use Mezzio\Valinor\ConfigProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigProvider::class)]
final class ConfigProviderTest extends TestCase
{
    public function testProvidesUsableServiceconfiguration(): void
    {
        self::assertInstanceOf(
            TreeMapper::class,
            new ServiceManager(new ConfigProvider()()['dependencies'])
                ->get(TreeMapper::class),
        );
    }
}
