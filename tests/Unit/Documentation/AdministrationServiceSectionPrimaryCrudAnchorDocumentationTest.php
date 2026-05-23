<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Documentation;

use PHPUnit\Framework\TestCase;

final class AdministrationServiceSectionPrimaryCrudAnchorDocumentationTest extends TestCase
{
    public function testPrimaryCrudAnchorCanonIsDocumented(): void
    {
        $path = dirname(__DIR__, 3).'/docs/architecture/030-service-section-primary-crud-anchors.adoc';
        $contents = file_get_contents($path);

        self::assertIsString($contents);
        self::assertStringContainsString('section-owned EasyAdmin CRUD index', $contents);
        self::assertStringContainsString('Borrowed fallback CRUD anchors are forbidden', $contents);
        self::assertStringContainsString('AdministrationManagingFieldControlRecordCrudController', $contents);
        self::assertStringContainsString('AdministrationSymfonyRouteRecordCrudController', $contents);
        self::assertStringContainsString('system/internal storage', $contents);
    }
}
