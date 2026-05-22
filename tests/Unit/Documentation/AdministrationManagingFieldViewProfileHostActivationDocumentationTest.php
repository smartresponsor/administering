<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Documentation;

use PHPUnit\Framework\TestCase;

final class AdministrationManagingFieldViewProfileHostActivationDocumentationTest extends TestCase
{
    public function testAdministeringDocumentsManagingStorageAsOptionalControlPlaneBoundary(): void
    {
        $root = dirname(__DIR__, 3);
        $contents = file_get_contents($root.'/docs/architecture/018-managing-field-view-profile-host-activation.adoc');

        self::assertIsString($contents);
        self::assertStringContainsString('App\\Managing\\HandlerInterface\\Crud\\ManageCrudFieldUserProfileApplyHandlerInterface', $contents);
        self::assertStringContainsString('system/internal database', $contents);
        self::assertStringContainsString('It must not be placed in user/business PostgreSQL storage.', $contents);
        self::assertStringContainsString('profile apply action is not a security grant', $contents);
    }
}
