<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Service\RuntimeScope;

use App\Administering\Service\RuntimeScope\AdministrationRuntimeScopeLockService;
use PHPUnit\Framework\TestCase;

final class AdministrationRuntimeScopeLockServiceTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'administering-runtime-lock-'.bin2hex(random_bytes(6));
        self::assertTrue(mkdir($this->directory, 0777, true));
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory.DIRECTORY_SEPARATOR.'*') ?: [] as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        if (is_dir($this->directory)) {
            rmdir($this->directory);
        }
    }

    public function testNormalizeReportsMissingLock(): void
    {
        $path = $this->directory.'/missing.php';

        $evidence = (new AdministrationRuntimeScopeLockService())->normalize($path);

        self::assertFalse($evidence->present);
        self::assertSame('missing', $evidence->status);
        self::assertNull($evidence->sha256);
        self::assertSame([], $evidence->enabledBundleTokens);
        self::assertSame([], $evidence->enabledComponents);
        self::assertSame([], $evidence->disabledComponents);
        self::assertSame(['Runtime scope lock is missing: '.$path], $evidence->errors);
        self::assertSame([], $evidence->warnings);
    }

    public function testNormalizeReportsUnreadableNonArrayLock(): void
    {
        $path = $this->writePhp('scalar.php', "'not-an-array'");

        $evidence = (new AdministrationRuntimeScopeLockService())->normalize($path);

        self::assertTrue($evidence->present);
        self::assertSame('unreadable', $evidence->status);
        self::assertNotNull($evidence->sha256);
        self::assertStringContainsString('Runtime scope lock must return an array.', $evidence->errors[0]);
    }

    public function testNormalizeReportsThrownLockException(): void
    {
        $path = $this->directory.'/throwing.php';
        file_put_contents($path, "<?php\n\nthrow new RuntimeException('lock exploded');\n");

        $evidence = (new AdministrationRuntimeScopeLockService())->normalize($path);

        self::assertSame('unreadable', $evidence->status);
        self::assertStringContainsString('lock exploded', $evidence->errors[0]);
    }

    public function testNormalizeCanonicalLockExtractsMetadataAndNormalizesTokens(): void
    {
        $path = $this->writeArray('canonical.php', [
            'schema' => 'app.kernel.runtime_scope.v1',
            'scope' => 'administering',
            'strict' => true,
            'sourceComposerFile' => 'composer.json',
            'sourceComposerSha256' => 'composer-sha',
            'sourceComposerPackageCount' => 12,
            'generatedAt' => '2026-09-24T18:00:00-05:00',
            'generatedBy' => 'runtime-scope:test',
            'enabledBundleTokens' => ['Viewing.bundle', 'administering.bundle', 'viewing.bundle'],
            'disabledComponents' => ['CrudingBundle', 'Objecting', 'cruding-bundle'],
        ]);

        $evidence = (new AdministrationRuntimeScopeLockService())->normalize($path);

        self::assertTrue($evidence->present);
        self::assertSame('present', $evidence->status);
        self::assertSame(hash_file('sha256', $path), $evidence->sha256);
        self::assertSame('app.kernel.runtime_scope.v1', $evidence->schema);
        self::assertSame('administering', $evidence->scope);
        self::assertTrue($evidence->strict);
        self::assertSame('composer.json', $evidence->sourceComposerFile);
        self::assertSame('composer-sha', $evidence->sourceComposerSha256);
        self::assertSame(12, $evidence->sourceComposerPackageCount);
        self::assertSame('2026-09-24T18:00:00-05:00', $evidence->generatedAt);
        self::assertSame('runtime-scope:test', $evidence->generatedBy);
        self::assertSame(['administering.bundle', 'viewing.bundle'], $evidence->enabledBundleTokens);
        self::assertSame(['administering', 'viewing'], $evidence->enabledComponents);
        self::assertSame(['cruding', 'cruding-bundle', 'objecting'], $evidence->disabledComponents);
        self::assertSame([], $evidence->errors);
        self::assertSame([], $evidence->warnings);
    }

    public function testNormalizeAcceptsLegacyEnabledComponentsWithWarning(): void
    {
        $path = $this->writeArray('legacy.php', [
            'schema' => 'app.kernel.runtime_scope.v1',
            'enabledComponents' => ['Viewing.bundle'],
            'disabledComponents' => [],
        ]);

        $evidence = (new AdministrationRuntimeScopeLockService())->normalize($path);

        self::assertSame('present', $evidence->status);
        self::assertSame(['viewing.bundle'], $evidence->enabledBundleTokens);
        self::assertSame(['viewing'], $evidence->enabledComponents);
        self::assertSame(
            ['Runtime scope lock uses legacy enabledComponents; regenerate lock to enabledBundleTokens.'],
            $evidence->warnings,
        );
    }

    public function testNormalizeCollectsValidationErrorsWarningsAndDeduplicates(): void
    {
        $path = $this->writeArray('invalid.php', [
            'schema' => 'wrong-schema',
            'enabledBundleTokens' => [null, '', 'App\\Feature\\FeatureBundle', 'custom-token', 'Viewing.bundle', 'viewing.bundle'],
            'disabledComponents' => [null, '', 'App\\Feature\\FeatureBundle', 'ViewingBundle', 'viewing'],
        ]);

        $evidence = (new AdministrationRuntimeScopeLockService())->normalize($path);

        self::assertSame('invalid', $evidence->status);
        self::assertSame(['custom-token', 'viewing.bundle'], $evidence->enabledBundleTokens);
        self::assertSame(['custom-token', 'viewing'], $evidence->enabledComponents);
        self::assertSame(['viewing'], $evidence->disabledComponents);
        self::assertContains('Runtime scope lock schema mismatch; expected app.kernel.runtime_scope.v1.', $evidence->errors);
        self::assertContains('enabledBundleTokens contains an invalid token string.', $evidence->errors);
        self::assertContains('enabledBundleTokens must not contain PHP class names: App\\Feature\\FeatureBundle', $evidence->errors);
        self::assertContains('disabledComponents contains an invalid component token.', $evidence->errors);
        self::assertContains('disabledComponents must not contain PHP class names: App\\Feature\\FeatureBundle', $evidence->errors);
        self::assertSame(
            ['Enabled bundle token does not follow component.bundle shape: custom-token'],
            $evidence->warnings,
        );
    }

    public function testNormalizeRejectsNonArrayTokenFields(): void
    {
        $path = $this->writeArray('invalid-fields.php', [
            'schema' => 'app.kernel.runtime_scope.v1',
            'enabledBundleTokens' => 'viewing.bundle',
            'disabledComponents' => 'objecting',
        ]);

        $evidence = (new AdministrationRuntimeScopeLockService())->normalize($path);

        self::assertSame('invalid', $evidence->status);
        self::assertContains('enabledBundleTokens must be an array.', $evidence->errors);
        self::assertContains('disabledComponents must be an array.', $evidence->errors);
        self::assertSame([], $evidence->enabledBundleTokens);
        self::assertSame([], $evidence->enabledComponents);
        self::assertSame([], $evidence->disabledComponents);
    }

    /** @param array<string, mixed> $payload */
    private function writeArray(string $name, array $payload): string
    {
        return $this->writePhp($name, var_export($payload, true));
    }

    private function writePhp(string $name, string $expression): string
    {
        $path = $this->directory.DIRECTORY_SEPARATOR.$name;
        self::assertNotFalse(file_put_contents($path, "<?php\n\nreturn ".$expression.";\n"));

        return $path;
    }
}
