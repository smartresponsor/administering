<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Documentation;

use PHPUnit\Framework\TestCase;

final class AdministrationServiceSectionCrudIndexMenuLinksDocumentationTest extends TestCase
{
    public function testDocumentationRecordsCrudIndexMenuLinksCanon(): void
    {
        $document = file_get_contents(dirname(__DIR__, 3).'/docs/architecture/033-service-section-crud-index-menu-links.adoc') ?: '';

        self::assertStringContainsString('MenuItem::linkToCrud', $document);
        self::assertStringContainsString('Crud::PAGE_INDEX', $document);
        self::assertStringContainsString('fallback/frontend links are forbidden', $document);
    }
}
