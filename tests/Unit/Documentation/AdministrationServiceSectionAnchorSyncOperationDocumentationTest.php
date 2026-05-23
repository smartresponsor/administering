<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Documentation;

use PHPUnit\Framework\TestCase;

final class AdministrationServiceSectionAnchorSyncOperationDocumentationTest extends TestCase
{
    public function testAnchorSyncOperationDocumentationKeepsOperationBoundary(): void
    {
        $document = file_get_contents(__DIR__.'/../../../docs/architecture/032-service-section-anchor-sync-operation.adoc');
        self::assertIsString($document);
        self::assertStringContainsString('administration.service_section_anchors.sync', $document);
        self::assertStringContainsString('primary EasyAdmin CRUD index', $document);
        self::assertStringContainsString('must not carry secrets', $document);
        self::assertStringContainsString('not a sidebar entry', $document);
    }
}
