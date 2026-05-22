<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Documentation;

use PHPUnit\Framework\TestCase;

final class AdministrationManagingFieldVisibilityExplanationDocumentationTest extends TestCase
{
    public function testDocumentationPinsReadOnlySafetyAndManagingContract(): void
    {
        $path = dirname(__DIR__, 3).'/docs/architecture/019-managing-field-visibility-explainability.adoc';
        $contents = (string) file_get_contents($path);

        self::assertStringContainsString('administration_managing_field_visibility_explanation', $contents);
        self::assertStringContainsString('ManageCrudFieldVisibilityExplanationResolverInterface', $contents);
        self::assertStringContainsString('read-only', strtolower($contents));
        self::assertStringContainsString('must not grant field access', strtolower($contents));
        self::assertStringContainsString('decision axes', strtolower($contents));
        self::assertStringContainsString('rolling_field_value_access_denied', $contents);
    }
}
