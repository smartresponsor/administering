<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Reader\RuntimeScope;

use App\Administering\Reader\RuntimeScope\AdministrationRuntimeScopeBundleCatalogReader;
use App\Administering\Reader\RuntimeScope\AdministrationRuntimeScopeComposerInventoryReader;
use App\Administering\Reader\RuntimeScope\AdministrationRuntimeScopeStateReader;
use App\Administering\Resolver\RuntimeScope\AdministrationRuntimeScopePathResolver;
use App\Administering\Service\RuntimeScope\AdministrationRuntimeScopeLockService;
use PHPUnit\Framework\TestCase;

final class AdministrationRuntimeScopeStateReaderTest extends TestCase
{
    private string $hostDir;

    protected function setUp(): void
    {
        $this->hostDir = sys_get_temp_dir().'/administering-state-reader-'.bin2hex(random_bytes(6));
        self::assertTrue(mkdir($this->hostDir.'/config/kernel', 0777, true));
    }

    protected function tearDown(): void
    {
        foreach (glob($this->hostDir.'/config/kernel/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->hostDir.'/config/kernel');
        @rmdir($this->hostDir.'/config');
        @unlink($this->hostDir.'/composer.json');
        @rmdir($this->hostDir);
    }

    public function testItAggregatesComposerCatalogAndLockState(): void
    {
        file_put_contents($this->hostDir.'/composer.json', json_encode([
            'require' => ['cruding/crud' => 'dev-master', 'viewing/view' => 'dev-master'],
        ], JSON_THROW_ON_ERROR));
        $this->writeLock([
            'schema' => 'app.kernel.runtime_scope.v1',
            'enabledBundleTokens' => ['viewing.bundle', 'cruding.bundle'],
            'disabledComponents' => ['interfacing'],
        ]);

        $state = $this->reader()->read($this->hostDir, 'dev');

        self::assertSame([], $state->sourceErrors);
        self::assertSame(['cruding' => 'cruding/crud', 'viewing' => 'viewing/view'], $state->composerComponentPackages);
        self::assertSame(['cruding', 'viewing'], $state->installedComponents);
        self::assertTrue($state->lockPresent);
        self::assertSame(['cruding.bundle', 'viewing.bundle'], $state->enabledBundleTokens);
        self::assertSame(['cruding', 'viewing'], $state->enabledComponents);
        self::assertSame(['interfacing'], $state->disabledComponents);
        self::assertSame([], $state->sourceErrors);
    }

    public function testItReportsMissingComposerAndLockWithoutThrowing(): void
    {
        $state = $this->reader()->read($this->hostDir, 'dev');

        self::assertFalse($state->lockPresent);
        self::assertSame([], $state->installedComponents);
        self::assertCount(2, $state->sourceErrors);
        self::assertStringContainsString('Composer inventory is missing:', $state->sourceErrors[0]);
        self::assertStringContainsString('Runtime scope lock is missing:', $state->sourceErrors[1]);
    }

    private function reader(): AdministrationRuntimeScopeStateReader
    {
        return new AdministrationRuntimeScopeStateReader(
            new AdministrationRuntimeScopePathResolver(dirname(__DIR__, 4)),
            new AdministrationRuntimeScopeComposerInventoryReader(),
            new AdministrationRuntimeScopeBundleCatalogReader(),
            new AdministrationRuntimeScopeLockService(),
        );
    }

    /** @param array<string, mixed> $payload */
    private function writeLock(array $payload): void
    {
        file_put_contents(
            $this->hostDir.'/config/kernel/runtime_scope.lock.php',
            "<?php\n\ndeclare(strict_types=1);\n\nreturn ".var_export($payload, true).";\n",
        );
    }
}
