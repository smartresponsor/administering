<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\RuntimeScope;

use App\Administering\Service\RuntimeScope\AdministrationRuntimeScopeLockNormalizer;
use PHPUnit\Framework\TestCase;

final class AdministrationRuntimeScopeLockNormalizerTest extends TestCase
{
    public function testItNormalizesBundleTokensToEnabledComponents(): void
    {
        $path = $this->writeLock([
            'schema' => 'app.kernel.runtime_scope.v1',
            'scope' => 'cruding,viewing',
            'strict' => true,
            'enabledBundleTokens' => ['viewing.bundle', 'cruding.bundle', 'viewing.bundle'],
            'disabledComponents' => ['interfacing'],
            'sourceComposerFile' => 'composer.json',
            'sourceComposerSha256' => str_repeat('a', 64),
            'sourceComposerPackageCount' => 2,
            'generatedAt' => '2026-06-05T00:00:00+00:00',
            'generatedBy' => 'administering-test',
        ]);

        $evidence = (new AdministrationRuntimeScopeLockNormalizer())->normalize($path);

        self::assertTrue($evidence->present);
        self::assertSame('present', $evidence->status);
        self::assertTrue($evidence->isValid());
        self::assertSame(['cruding.bundle', 'viewing.bundle'], $evidence->enabledBundleTokens);
        self::assertSame(['cruding', 'viewing'], $evidence->enabledComponents);
        self::assertSame(['interfacing'], $evidence->disabledComponents);
        self::assertSame([], $evidence->errors);
    }

    public function testItRejectsForeignClassNamesInBundleTokens(): void
    {
        $path = $this->writeLock([
            'schema' => 'app.kernel.runtime_scope.v1',
            'enabledBundleTokens' => [$this->foreignClassName('Accessing', 'AccessingBundle')],
            'disabledComponents' => [],
        ]);

        $evidence = (new AdministrationRuntimeScopeLockNormalizer())->normalize($path);

        self::assertTrue($evidence->present);
        self::assertSame('invalid', $evidence->status);
        self::assertFalse($evidence->isValid());
        self::assertSame([], $evidence->enabledBundleTokens);
        self::assertSame([], $evidence->enabledComponents);
        self::assertContains('enabledBundleTokens must not contain PHP class names: '.$this->foreignClassName('Accessing', 'AccessingBundle'), $evidence->errors);
    }

    public function testItRejectsForeignClassNamesInDisabledComponents(): void
    {
        $path = $this->writeLock([
            'schema' => 'app.kernel.runtime_scope.v1',
            'enabledBundleTokens' => ['cruding.bundle'],
            'disabledComponents' => [$this->foreignClassName('Rolling', 'RollingBundle')],
        ]);

        $evidence = (new AdministrationRuntimeScopeLockNormalizer())->normalize($path);

        self::assertSame('invalid', $evidence->status);
        self::assertContains('disabledComponents must not contain PHP class names: '.$this->foreignClassName('Rolling', 'RollingBundle'), $evidence->errors);
    }

    private function foreignClassName(string $component, string $class): string
    {
        return implode('\\', ['App', $component, $class]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function writeLock(array $payload): string
    {
        $path = tempnam(sys_get_temp_dir(), 'administering-runtime-lock-');
        self::assertIsString($path);

        file_put_contents($path, "<?php\n\ndeclare(strict_types=1);\n\nreturn ".var_export($payload, true).";\n");

        return $path;
    }
}
