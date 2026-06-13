<?php

declare(strict_types=1);

namespace App\Administering\Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class AdministrationCrudDirectionsPhysicalContractTest extends TestCase
{
    public function testAdministrationCrudDirectionTargetsExistPhysically(): void
    {
        $mapFile = dirname(__DIR__, 2).'/config/platform/routes/crud/administration-directions.yaml';

        self::assertFileExists($mapFile);

        $content = (string) file_get_contents($mapFile);
        preg_match_all("/\n\s+(service|type): '([^']+)'/", $content, $matches, PREG_SET_ORDER);

        self::assertNotSame([], $matches);

        foreach ($matches as $match) {
            $className = $match[2];
            self::assertMatchesRegularExpression('/^App\\\\(Service|Form)\\\\/', $className);
            self::assertTrue(
                class_exists($className),
                sprintf('Administration CRUD direction target must exist physically: %s', $className),
            );
        }
    }

    public function testAdministrationCrudDirectionsKeepReservedRoot(): void
    {
        $mapFile = dirname(__DIR__, 2).'/config/platform/routes/crud/administration-directions.yaml';
        $content = (string) file_get_contents($mapFile);
        preg_match_all("/\n\s+path: '([^']+)'/", $content, $matches);

        self::assertNotSame([], $matches[1]);

        foreach ($matches[1] as $path) {
            self::assertStringStartsWith('/administration/', $path);
            self::assertStringNotContainsString('{ref}', $path);
        }
    }
}
