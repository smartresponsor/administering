<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Documentation;

use PHPUnit\Framework\TestCase;

final class AdministrationServiceSectionAnchorSyncDocumentationTest extends TestCase
{
    public function testAnchorSyncDocumentationKeepsSidebarAndCrudIndexCanon(): void
    {
        $document = file_get_contents(__DIR__.'/../../../docs/architecture/031-service-section-anchor-sync.adoc');
        self::assertIsString($document);
        self::assertStringContainsString('src/Service/<Section>', $document);
        self::assertStringContainsString('primary EasyAdmin CRUD index', $document);
        self::assertStringContainsString('administering:service-section-anchors:sync', $document);
        self::assertStringContainsString('must not become left-menu items', $document);
    }
}
