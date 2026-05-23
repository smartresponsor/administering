<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Documentation;

use PHPUnit\Framework\TestCase;

final class AdministrationComponentRootMenuFirstFiveDocumentationTest extends TestCase
{
    public function testDocumentCapturesFirstFiveComponentRootMenu(): void
    {
        $path = __DIR__.'/../../../docs/architecture/025-administering-component-root-menu-first-five.adoc';
        self::assertFileExists($path);

        $contents = (string) file_get_contents($path);
        foreach (['Dashboard', 'Rolling', 'Accessing', 'Managing', 'Symfony'] as $token) {
            self::assertStringContainsString($token, $contents);
        }

        self::assertStringContainsString('component/platform roots', $contents);
        self::assertStringContainsString('technical sitemap', $contents);
    }
}
