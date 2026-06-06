<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Admin;

use App\Administering\Catalog\Admin\AdministrationFilesystemServiceToolCatalog;
use App\Administering\Catalog\Admin\AdministrationServiceSectionCatalog;
use App\Administering\Catalog\Admin\AdministrationServiceToolScreenCatalog;
use App\Administering\Provider\Admin\AdministrationServiceSectionToolDashboardProvider;
use PHPUnit\Framework\TestCase;

final class AdministrationServiceToolDetailProviderTest extends TestCase
{
    public function testItBuildsDetailForKnownTool(): void
    {
        $sectionCatalog = new AdministrationServiceSectionCatalog();
        $toolCatalog = new AdministrationFilesystemServiceToolCatalog($sectionCatalog, new AdministrationServiceToolScreenCatalog());
        $provider = new AdministrationServiceSectionToolDashboardProvider($sectionCatalog, $toolCatalog);

        $detail = $provider->detailForTool('Connected', 'AdministrationConnectedComponentOverviewProvider');

        self::assertNotNull($detail);
        self::assertSame('Connected', $detail->section->key);
        self::assertSame('administration_connected_component_overview', $detail->tool->primaryRouteName);
    }
}
