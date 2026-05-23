<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Admin;

use App\Administering\Catalog\Admin\AdministrationServiceSectionCatalog;
use App\Administering\Catalog\Admin\AdministrationServiceToolScreenCatalog;
use App\Administering\Catalog\Admin\FilesystemAdministrationServiceToolCatalog;
use App\Administering\Provider\Admin\AdministrationServiceSectionToolDashboardProvider;
use PHPUnit\Framework\TestCase;

final class AdministrationServiceToolDetailProviderTest extends TestCase
{
    public function testItBuildsDetailForKnownTool(): void
    {
        $sectionCatalog = new AdministrationServiceSectionCatalog();
        $toolCatalog = new FilesystemAdministrationServiceToolCatalog($sectionCatalog, new AdministrationServiceToolScreenCatalog());
        $provider = new AdministrationServiceSectionToolDashboardProvider($sectionCatalog, $toolCatalog);

        $detail = $provider->detailForTool('Managing', 'AdministrationManagingFieldAccessCatalogProvider');

        self::assertNotNull($detail);
        self::assertSame('Managing', $detail->section->key);
        self::assertSame('administration_managing_field_access_catalog', $detail->tool->primaryRouteName);
    }
}
