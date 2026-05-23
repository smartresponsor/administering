<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Admin;

use App\Administering\Catalog\Admin\AdministrationServiceToolScreenCatalog;
use PHPUnit\Framework\TestCase;

final class AdministrationServiceToolScreenCatalogTest extends TestCase
{
    public function testItMapsKnownServiceToolToPrimaryScreen(): void
    {
        $catalog = new AdministrationServiceToolScreenCatalog();

        $screen = $catalog->screenForTool('Managing', 'AdministrationManagingFieldAccessCatalogProvider');

        self::assertNotNull($screen);
        self::assertSame('administration_managing_field_access_catalog', $screen->routeName);
        self::assertSame('Open access catalog', $screen->label);
    }

    public function testItKeepsUnmappedToolsAsServiceOnly(): void
    {
        $catalog = new AdministrationServiceToolScreenCatalog();

        self::assertNull($catalog->screenForTool('Audit', 'DoctrineAdministrationAuditRecorder'));
    }
}
