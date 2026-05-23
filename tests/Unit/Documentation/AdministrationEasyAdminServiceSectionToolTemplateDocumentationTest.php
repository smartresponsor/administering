<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Documentation;

use PHPUnit\Framework\TestCase;

final class AdministrationEasyAdminServiceSectionToolTemplateDocumentationTest extends TestCase
{
    public function testDocumentationRecordsEasyAdminServiceSectionTemplateCanon(): void
    {
        $root = dirname(__DIR__, 3);
        $documentation = file_get_contents($root.'/docs/architecture/027-easy-admin-service-section-tool-templates.adoc');
        $template = file_get_contents($root.'/templates/easy_admin/service_section_tools.html.twig');

        self::assertIsString($documentation);
        self::assertStringContainsString('src/Service/<Section>', $documentation);
        self::assertStringContainsString('primary EasyAdmin CRUD index', $documentation);
        self::assertStringContainsString('@EasyAdmin/page/content.html.twig', $template ?: '');
    }
}
