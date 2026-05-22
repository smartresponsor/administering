<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Documentation;

use PHPUnit\Framework\TestCase;

final class AdministrationManagingRollingFieldAccessAdapterDocumentationTest extends TestCase
{
    public function testAdministeringDocumentsManagingRollingAdapterBoundary(): void
    {
        $root = dirname(__DIR__, 3);
        $contents = file_get_contents($root.'/docs/architecture/020-managing-rolling-field-access-adapter.adoc');

        self::assertIsString($contents);
        self::assertStringContainsString('crud_field_external_access_backend: rolling', $contents);
        self::assertStringContainsString('Administering remains the control plane', $contents);
        self::assertStringContainsString('Field view profiles remain presentation-only', $contents);
    }
}
