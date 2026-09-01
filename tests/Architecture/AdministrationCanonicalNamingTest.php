<?php

declare(strict_types=1);

namespace App\Administering\Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class AdministrationCanonicalNamingTest extends TestCase
{
    private const LEGACY_OWNER_STEMS = [
        'DoctrineAdministration',
        'FilesystemAdministration',
        'RollingAdministration',
        'AccessingAdministration',
        'SymfonyAdministration',
        'BootstrapAdministration',
        'MessengerAdministration',
        'NullAdministration',
    ];

    public function testProductionPhpFilesDoNotUseLegacyOwnerStemOrdering(): void
    {
        $violations = [];

        foreach ($this->phpFiles(dirname(__DIR__, 2).'/src') as $file) {
            $basename = $file->getBasename('.php');

            foreach (self::LEGACY_OWNER_STEMS as $legacyStem) {
                if (str_starts_with($basename, $legacyStem)) {
                    $violations[] = $file->getPathname();
                }
            }
        }

        self::assertSame([], $violations, "Legacy Administering owner-stem ordering must not return.\n".implode("\n", $violations));
    }

    public function testTraitsUseCanonicalTraitLayer(): void
    {
        $root = dirname(__DIR__, 2);

        self::assertDirectoryDoesNotExist($root.'/src/ServiceTrait');
        self::assertFileExists($root.'/src/Trait/Admin/AdministrationServiceSectionAnchorSyncToolHandlerTrait.php');
    }

    public function testAdminNoStoreSubscriberUsesOwnerStem(): void
    {
        $root = dirname(__DIR__, 2);

        self::assertFileDoesNotExist($root.'/src/Subscriber/Http/AdminNoStoreSubscriber.php');
        self::assertFileExists($root.'/src/Subscriber/Http/AdministrationAdminNoStoreSubscriber.php');
    }

    /** @return iterable<SplFileInfo> */
    private function phpFiles(string $directory): iterable
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && 'php' === $file->getExtension()) {
                yield $file;
            }
        }
    }
}
