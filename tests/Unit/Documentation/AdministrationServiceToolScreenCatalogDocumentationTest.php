<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Documentation;

use PHPUnit\Framework\TestCase;

final class AdministrationServiceToolScreenCatalogDocumentationTest extends TestCase
{
    public function testServiceToolScreenCatalogIsDocumented(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 3).'/docs/architecture/029-service-tool-screen-catalog.adoc');

        self::assertIsString($contents);
        self::assertStringContainsString('AdministrationServiceToolScreenCatalog', $contents);
        self::assertStringContainsString('FilesystemAdministrationServiceToolCatalog', $contents);
        self::assertStringContainsString('must not contain route-name knowledge', $contents);
        self::assertStringContainsString('Unmapped tools remain visible as service-only tools', $contents);
    }
}
