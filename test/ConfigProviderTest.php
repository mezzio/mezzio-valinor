<?php

declare(strict_types=1);

namespace MezzioTest\Valinor;

use Mezzio\Valinor\ConfigProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigProvider::class)]
final class ConfigProviderTest extends TestCase
{
    public function testInvocationReturnsArray(): void
    {
        $config = new ConfigProvider()();

        $this->assertArrayHasKey('dependencies', $config);
        $this->assertIsArray($config['dependencies']);
    }
}
