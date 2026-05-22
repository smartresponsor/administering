<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Documentation;

use PHPUnit\Framework\TestCase;

final class AdministrationManagingFieldViewProfileStorageCheckDocumentationTest extends TestCase
{
    public function testHostActivationDocReferencesManagingStorageCheckCommand(): void
    {
        $doc = file_get_contents(dirname(__DIR__, 3).'/docs/architecture/018-managing-field-view-profile-host-activation.adoc');

        self::assertIsString($doc);
        self::assertStringContainsString('managing:field-view-profile-storage:check', $doc);
        self::assertStringContainsString('does not grant field access', $doc);
    }
}
