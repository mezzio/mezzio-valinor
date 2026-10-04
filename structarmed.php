<?php

declare(strict_types=1);

use Boundwize\StructArmed\Architecture;
use Boundwize\StructArmed\Preset\Preset;

return Architecture::define()
    ->withPresets(Preset::PSR4(), Preset::CODEQUALITY())
    ->layer('Factory', [
        'src/DefaultMapperBuilderFactory.php',
        'src/TreeMapperFactory.php',
    ])
    ->layer('ConfigProvider', 'src/ConfigProvider.php')
    ->ruleset([
        'Factory'        => [],
        'ConfigProvider' => ['+Factory'],
    ]);
