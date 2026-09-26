<?php

declare(strict_types=1);

namespace App\Administering\Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class AdministrationCrudDirectionsPhysicalContractTest extends TestCase
{
    public function testAdministrationDoesNotOwnGenericCrudDirectionMap(): void
    {
        $mapFile = dirname(__DIR__, 2).'/config/platform/routes/crud/administration-directions.yaml';

        self::assertFileDoesNotExist(
            $mapFile,
            'Canon021 assigns generic application CRUD routing to Cruding; Administering must not restore a local direction map.',
        );
    }

    public function testCrudingIsTheDeclaredGenericCrudOwnerDependency(): void
    {
        $composerFile = dirname(__DIR__, 2).'/composer.json';
        $composer = json_decode((string) file_get_contents($composerFile), true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($composer);
        self::assertSame('dev-master', $composer['require']['cruding/crud'] ?? null);
    }
}
