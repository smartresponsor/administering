<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Documentation;

use PHPUnit\Framework\TestCase;

final class AdministrationServiceToolCatalogDocumentationTest extends TestCase
{
    public function testServiceToolCatalogCanonIsDocumented(): void
    {
        $path = dirname(__DIR__, 3).'/docs/architecture/026-service-tool-catalog.adoc';
        $contents = file_get_contents($path);

        self::assertIsString($contents);
        self::assertStringContainsString('one menu section equals one direct `src/Service/<Section>` directory', $contents);
        self::assertStringContainsString('one direct service PHP file equals one section tool', $contents);
        self::assertStringContainsString('FilesystemAdministrationServiceToolCatalog', $contents);
        self::assertStringContainsString('section dashboard cards/tools', $contents);
    }
}
