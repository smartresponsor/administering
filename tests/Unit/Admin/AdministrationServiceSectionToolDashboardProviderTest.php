<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Admin;

use App\Administering\Catalog\Admin\AdministrationServiceSectionCatalog;
use App\Administering\Catalog\Admin\AdministrationServiceToolScreenCatalog;
use App\Administering\Catalog\Admin\FilesystemAdministrationServiceToolCatalog;
use App\Administering\Provider\Admin\AdministrationServiceSectionToolDashboardProvider;
use PHPUnit\Framework\TestCase;

final class AdministrationServiceSectionToolDashboardProviderTest extends TestCase
{
    public function testItBuildsDashboardForCanonicalServiceSection(): void
    {
        $sectionCatalog = new AdministrationServiceSectionCatalog();
        $toolCatalog = new FilesystemAdministrationServiceToolCatalog($sectionCatalog, new AdministrationServiceToolScreenCatalog());
        $provider = new AdministrationServiceSectionToolDashboardProvider($sectionCatalog, $toolCatalog);

        $dashboard = $provider->dashboardForSection('Rolling');

        self::assertNotNull($dashboard);
        self::assertSame('Rolling', $dashboard->section->key);
        self::assertGreaterThan(0, $dashboard->toolCount());
    }

    public function testItReturnsNullForUnknownSection(): void
    {
        $sectionCatalog = new AdministrationServiceSectionCatalog();
        $toolCatalog = new FilesystemAdministrationServiceToolCatalog($sectionCatalog, new AdministrationServiceToolScreenCatalog());
        $provider = new AdministrationServiceSectionToolDashboardProvider($sectionCatalog, $toolCatalog);

        self::assertNull($provider->dashboardForSection('Unknown'));
    }
}
