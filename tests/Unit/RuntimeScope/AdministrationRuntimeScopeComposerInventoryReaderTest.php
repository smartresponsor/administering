<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\RuntimeScope;

use App\Administering\Reader\RuntimeScope\AdministrationRuntimeScopeComposerInventoryReader;
use PHPUnit\Framework\TestCase;

final class AdministrationRuntimeScopeComposerInventoryReaderTest extends TestCase
{
    public function testInventoryMapsOnlyCatalogPackagesToRuntimeComponents(): void
    {
        $composerPath = $this->writeComposer([
            'require' => [
                'php' => '^8.4',
                'symfony/framework-bundle' => '^8.0',
                'cruding/crud' => '@dev',
                'unknown/runtime' => '@dev',
            ],
            'require-dev' => [
                'viewing/view' => '@dev',
                'phpunit/phpunit' => '^12.0',
            ],
        ]);

        $inventory = (new AdministrationRuntimeScopeComposerInventoryReader())->inventory($composerPath, [
            'components' => [
                'cruding' => [
                    'package' => 'cruding/crud',
                    'bundleToken' => 'cruding.bundle',
                ],
                'viewing' => [
                    'package' => 'viewing/view',
                    'bundleToken' => 'viewing.bundle',
                ],
            ],
        ]);

        self::assertSame(['cruding', 'viewing'], $inventory->installedComponents());
        self::assertSame('cruding/crud', $inventory->packageForComponent('cruding'));
        self::assertSame('viewing/view', $inventory->packageForComponent('viewing'));
        self::assertNull($inventory->packageForComponent('unknown'));
        self::assertContains('unknown/runtime', $inventory->ignoredRuntimeScopePackages);
        self::assertArrayHasKey('symfony/framework-bundle', $inventory->packages);
    }

    public function testLegacyPackagesMethodRemainsRawPackageInventoryOnly(): void
    {
        $composerPath = $this->writeComposer([
            'require' => [
                'cruding/crud' => '@dev',
            ],
        ]);

        self::assertSame(
            ['cruding/crud' => true],
            (new AdministrationRuntimeScopeComposerInventoryReader())->packages($composerPath),
        );
    }

    /** @param array<string, mixed> $payload */
    private function writeComposer(array $payload): string
    {
        $path = tempnam(sys_get_temp_dir(), 'administering-composer-inventory-');
        self::assertIsString($path);

        file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        return $path;
    }
}
