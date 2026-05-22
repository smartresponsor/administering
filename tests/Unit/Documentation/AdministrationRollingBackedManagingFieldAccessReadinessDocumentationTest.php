<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Documentation;

use PHPUnit\Framework\TestCase;

final class AdministrationRollingBackedManagingFieldAccessReadinessDocumentationTest extends TestCase
{
    public function testDocumentsRollingBackedManagingAccessReadinessBoundary(): void
    {
        $doc = file_get_contents(__DIR__.'/../../../docs/architecture/021-rolling-backed-managing-field-access-readiness.adoc');

        self::assertIsString($doc);
        self::assertStringContainsString('crud_field_external_access_backend: rolling', $doc);
        self::assertStringContainsString('crud_field_external_access_failure_effect: deny', $doc);
        self::assertStringContainsString('managing.field.view', $doc);
        self::assertStringContainsString('read-only', strtolower($doc));
    }
}
