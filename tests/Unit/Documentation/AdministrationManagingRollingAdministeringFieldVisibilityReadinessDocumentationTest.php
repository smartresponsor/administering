<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Documentation;

use PHPUnit\Framework\TestCase;

final class AdministrationManagingRollingAdministeringFieldVisibilityReadinessDocumentationTest extends TestCase
{
    public function testReadinessDocumentPinsThreeComponentOwnershipAndSafetyRules(): void
    {
        $doc = file_get_contents(__DIR__.'/../../../docs/architecture/023-managing-rolling-administering-field-visibility-readiness.adoc');

        self::assertIsString($doc);
        self::assertStringContainsString('Managing', $doc);
        self::assertStringContainsString('Rolling', $doc);
        self::assertStringContainsString('Administering', $doc);
        self::assertStringContainsString('managing.field.view', $doc);
        self::assertStringContainsString('Profile storage is system/internal storage', $doc);
        self::assertStringContainsString('managing:field-view-profile-storage:check', $doc);
        self::assertStringContainsString('crud_field_external_access_failure_effect: deny', $doc);
        self::assertStringContainsString('rolling_field_value_access_denied', $doc);
        self::assertStringContainsString('user_profile_hidden', $doc);
    }
}
