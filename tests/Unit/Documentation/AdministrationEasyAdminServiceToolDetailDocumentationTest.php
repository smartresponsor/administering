<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Documentation;

use PHPUnit\Framework\TestCase;

final class AdministrationEasyAdminServiceToolDetailDocumentationTest extends TestCase
{
    public function testDocumentationLocksToolDetailTemplateRule(): void
    {
        $doc = file_get_contents(__DIR__.'/../../../docs/architecture/028-easy-admin-service-tool-detail-pages.adoc');

        self::assertIsString($doc);
        self::assertStringContainsString('@EasyAdmin/page/content.html.twig', $doc);
        self::assertStringContainsString('src/Service/<Section>', $doc);
        self::assertStringContainsString('State-changing actions must remain behind their existing reviewed surfaces', $doc);
    }
}
