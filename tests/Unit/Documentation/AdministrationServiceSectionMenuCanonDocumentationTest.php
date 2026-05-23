<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Documentation;

use PHPUnit\Framework\TestCase;

final class AdministrationServiceSectionMenuCanonDocumentationTest extends TestCase
{
    public function testServiceSectionMenuCanonIsDocumented(): void
    {
        $path = dirname(__DIR__, 3).'/docs/architecture/025-service-section-menu-canon.adoc';
        $contents = file_get_contents($path);

        self::assertIsString($contents);
        self::assertStringContainsString('The Administering left menu is a service-section map', $contents);
        self::assertStringContainsString('src/Service/<Section>', $contents);
        self::assertStringContainsString('primary EasyAdmin CRUD index', $contents);
        self::assertStringContainsString('AdministrationServiceSectionCatalog', $contents);
        self::assertStringContainsString('src/Service/Managing', $contents);
        self::assertStringContainsString('do not live under `src/Service/Security`', $contents);
    }
}
