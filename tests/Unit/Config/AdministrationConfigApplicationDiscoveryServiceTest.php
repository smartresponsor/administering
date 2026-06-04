<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Config;

use App\Administering\Service\Config\AdministrationConfigApplicationDiscoveryService;
use PHPUnit\Framework\TestCase;

final class AdministrationConfigApplicationDiscoveryServiceTest extends TestCase
{
    public function testDiscoverReadsComponentManifest(): void
    {
        $projectDir = sys_get_temp_dir().'/administering-config-discovery-'.bin2hex(random_bytes(4));
        $componentDir = $projectDir.'/../DemoComponent';
        @mkdir($componentDir.'/config/component', 0775, true);
        file_put_contents($componentDir.'/config/component/component.yaml', <<<'YAML'
component: DemoComponent
title: Demo Component
package: demo/component
namespace: App\DemoComponent
ui_label: Demo
YAML);

        $service = new AdministrationConfigApplicationDiscoveryService($projectDir, ['DemoComponent']);
        $applications = $service->discover();

        self::assertCount(1, $applications);
        self::assertSame('DemoComponent', $applications[0]->applicationCode);
        self::assertSame('Demo', $applications[0]->label);
        self::assertTrue($applications[0]->enabled);
    }
}
