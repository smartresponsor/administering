<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Value\RuntimeScope;

use App\Administering\Value\RuntimeScope\AdministrationRuntimeScopeLockEvidence;
use PHPUnit\Framework\TestCase;

final class AdministrationRuntimeScopeLockEvidenceTest extends TestCase
{
    public function testValidityReflectsErrors(): void
    {
        self::assertTrue($this->evidence()->isValid());
        self::assertFalse($this->evidence(errors: ['invalid'])->isValid());
    }

    public function testAgeValidationIsDisabledForNonPositiveMaxAge(): void
    {
        $evidence = $this->evidence(generatedAt: null);

        self::assertSame([], $evidence->ageErrors(0));
        self::assertSame([], $evidence->ageErrors(-1));
    }

    public function testAgeValidationRequiresGeneratedAt(): void
    {
        self::assertSame(
            ['Runtime scope lock generatedAt is missing while max-age validation is enabled.'],
            $this->evidence(generatedAt: null)->ageErrors(60),
        );
        self::assertSame(
            ['Runtime scope lock generatedAt is missing while max-age validation is enabled.'],
            $this->evidence(generatedAt: '')->ageErrors(60),
        );
    }

    public function testAgeValidationRejectsInvalidTimestamp(): void
    {
        self::assertSame(
            ['Runtime scope lock generatedAt is invalid: definitely-not-a-date'],
            $this->evidence(generatedAt: 'definitely-not-a-date')->ageErrors(60),
        );
    }

    public function testAgeValidationReportsStaleLock(): void
    {
        $generatedAt = (new \DateTimeImmutable('-2 hours'))->format(DATE_ATOM);

        self::assertSame(
            ['Runtime scope lock is older than 60 seconds.'],
            $this->evidence(generatedAt: $generatedAt)->ageErrors(60),
        );
    }

    public function testAgeValidationAcceptsFreshLock(): void
    {
        $generatedAt = (new \DateTimeImmutable('-5 seconds'))->format(DATE_ATOM);

        self::assertSame([], $this->evidence(generatedAt: $generatedAt)->ageErrors(60));
    }

    public function testToArrayPreservesCanonicalEvidenceShape(): void
    {
        $evidence = $this->evidence(
            generatedAt: '2026-09-24T18:00:00-05:00',
            errors: [],
            warnings: ['legacy token'],
        );

        self::assertSame([
            'path' => 'config/kernel/runtime_scope.lock.php',
            'present' => true,
            'status' => 'present',
            'sha256' => 'lock-sha',
            'schema' => 'app.kernel.runtime_scope.v1',
            'scope' => 'administering',
            'strict' => true,
            'sourceComposerFile' => 'composer.json',
            'sourceComposerSha256' => 'composer-sha',
            'sourceComposerPackageCount' => 12,
            'generatedAt' => '2026-09-24T18:00:00-05:00',
            'generatedBy' => 'runtime-scope:test',
            'enabledBundleTokens' => ['administering.bundle'],
            'enabledComponents' => ['administering'],
            'disabledComponents' => ['viewing'],
            'errors' => [],
            'warnings' => ['legacy token'],
        ], $evidence->toArray());
    }

    /**
     * @param list<string> $errors
     * @param list<string> $warnings
     */
    private function evidence(
        ?string $generatedAt = '2026-09-24T18:00:00-05:00',
        array $errors = [],
        array $warnings = [],
    ): AdministrationRuntimeScopeLockEvidence {
        return new AdministrationRuntimeScopeLockEvidence(
            path: 'config/kernel/runtime_scope.lock.php',
            present: true,
            status: [] === $errors ? 'present' : 'invalid',
            sha256: 'lock-sha',
            schema: 'app.kernel.runtime_scope.v1',
            scope: 'administering',
            strict: true,
            sourceComposerFile: 'composer.json',
            sourceComposerSha256: 'composer-sha',
            sourceComposerPackageCount: 12,
            generatedAt: $generatedAt,
            generatedBy: 'runtime-scope:test',
            enabledBundleTokens: ['administering.bundle'],
            enabledComponents: ['administering'],
            disabledComponents: ['viewing'],
            errors: $errors,
            warnings: $warnings,
        );
    }
}
