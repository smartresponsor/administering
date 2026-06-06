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

        $screen = $catalog->screenForTool('Connected', 'AdministrationConnectedComponentOverviewProvider');

        self::assertNotNull($screen);
        self::assertSame('administration_connected_component_overview', $screen->routeName);
        self::assertSame('Open overview', $screen->label);
    }

    public function testItKeepsUnmappedToolsAsServiceOnly(): void
    {
        $catalog = new AdministrationServiceToolScreenCatalog();

        self::assertNull($catalog->screenForTool('Audit', 'AdministrationDoctrineAuditRecorder'));
    }
}
