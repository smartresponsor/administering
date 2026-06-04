<?php

declare(strict_types=1);

namespace App\Administering\Service\RuntimeScope;

use App\Administering\Scanner\RuntimeScope\AdministrationRuntimeScopeConfigLeakScanner;
use App\Administering\Value\RuntimeScope\AdministrationRuntimeScopeValidationResult;

final readonly class AdministrationRuntimeScopeValidationService
{
    public function __construct(
        private AdministrationRuntimeScopePathResolver $pathResolver,
        private AdministrationRuntimeScopeConfigLeakScanner $configLeakScanner,
    ) {
    }

    public function validate(string $hostDir, string $environment, int $maxAgeSeconds): AdministrationRuntimeScopeValidationResult
    {
        $hostDir = $this->pathResolver->absolutePath($hostDir);
        $errors = [];
        $warnings = [];
        $lockFile = $this->pathResolver->lockPath($hostDir, $environment);
        $composerFile = $this->pathResolver->composerFile($environment);
        $composerPath = $hostDir.'/'.$composerFile;
        $enabledBundleCount = 0;
        $disabledComponentCount = 0;

        if (!is_file($lockFile)) {
            $errors[] = sprintf('Runtime scope lock is missing: %s', $lockFile);
        }

        if (!is_file($composerPath)) {
            $errors[] = sprintf('Composer inventory is missing: %s', $composerPath);
        }

        if ([] === $errors) {
            try {
                $payload = require $lockFile;
                if (!is_array($payload)) {
                    throw new \RuntimeException('Lock file must return an array.');
                }

                $this->validatePayloadSchema($payload, $composerFile, $composerPath, $errors);
                $enabledBundleCount = $this->validateEnabledBundles($payload, $environment, $errors, $warnings);
                $disabledComponents = $this->disabledComponents($payload);
                $disabledComponentCount = count($disabledComponents);
                $this->validateConfigLeaks($hostDir, $disabledComponents, $errors);

                if ($maxAgeSeconds > 0) {
                    $this->validateGeneratedAt($payload, $maxAgeSeconds, $errors);
                }
            } catch (\Throwable $exception) {
                $errors[] = sprintf('Unable to validate runtime scope lock %s: %s', $lockFile, $exception->getMessage());
            }
        }

        return new AdministrationRuntimeScopeValidationResult(
            $hostDir,
            $environment,
            $lockFile,
            $composerFile,
            $enabledBundleCount,
            $disabledComponentCount,
            array_values(array_unique($errors)),
            array_values(array_unique($warnings)),
        );
    }

    /** @param array<string, mixed> $payload @param list<string> $errors */
    private function validatePayloadSchema(array $payload, string $composerFile, string $composerPath, array &$errors): void
    {
        if (($payload['schema'] ?? null) !== 'app.kernel.runtime_scope.v1') {
            $errors[] = 'Runtime scope lock schema mismatch; expected app.kernel.runtime_scope.v1.';
        }

        if (($payload['sourceComposerFile'] ?? null) !== $composerFile) {
            $errors[] = sprintf(
                'Runtime scope sourceComposerFile mismatch: expected %s, got %s.',
                $composerFile,
                is_scalar($payload['sourceComposerFile'] ?? null) ? (string) $payload['sourceComposerFile'] : 'null',
            );
        }

        $expectedSha = hash_file('sha256', $composerPath) ?: null;
        if (is_string($payload['sourceComposerSha256'] ?? null) && $payload['sourceComposerSha256'] !== $expectedSha) {
            $errors[] = sprintf('Runtime scope composer fingerprint is stale for %s.', $composerFile);
        }
    }

    /** @param array<string, mixed> $payload @param list<string> $errors @param list<string> $warnings */
    private function validateEnabledBundles(array $payload, string $environment, array &$errors, array &$warnings): int
    {
        $enabledBundles = $payload['enabledBundles'] ?? [];
        if (!is_array($enabledBundles)) {
            $errors[] = 'enabledBundles must be an array.';
            $enabledBundles = [];
        }

        $strict = is_bool($payload['strict'] ?? null) ? $payload['strict'] : ('prod' === $environment);
        foreach ($enabledBundles as $bundleClass) {
            if (!is_string($bundleClass) || '' === $bundleClass) {
                $errors[] = 'enabledBundles contains an invalid bundle class.';
                continue;
            }

            if (!class_exists($bundleClass)) {
                $message = sprintf('Enabled bundle class is not autoloadable: %s', $bundleClass);
                if ($strict) {
                    $errors[] = $message;
                } else {
                    $warnings[] = $message;
                }
            }
        }

        return count($enabledBundles);
    }

    /** @param array<string, mixed> $payload @return list<string> */
    private function disabledComponents(array $payload): array
    {
        $disabledComponents = [];
        $disabledPayload = $payload['disabledComponents'] ?? [];
        if (is_array($disabledPayload)) {
            foreach ($disabledPayload as $component) {
                if (is_string($component) && '' !== trim($component)) {
                    $disabledComponents[] = strtolower(trim($component));
                }
            }
        }

        return array_values(array_unique($disabledComponents));
    }

    /** @param list<string> $disabledComponents @param list<string> $errors */
    private function validateConfigLeaks(string $hostDir, array $disabledComponents, array &$errors): void
    {
        foreach ($this->configLeakScanner->scan($hostDir, $disabledComponents) as $finding) {
            $errors[] = sprintf(
                'Disabled component "%s" leaks into %s:%d through pattern "%s".',
                $finding['component'],
                $finding['file'],
                $finding['line'],
                $finding['pattern'],
            );
        }
    }

    /** @param array<string, mixed> $payload @param list<string> $errors */
    private function validateGeneratedAt(array $payload, int $maxAgeSeconds, array &$errors): void
    {
        $generatedAt = $payload['generatedAt'] ?? null;
        if (!is_string($generatedAt) || '' === $generatedAt) {
            $errors[] = 'generatedAt is missing while max-age validation is enabled.';

            return;
        }

        try {
            $generatedAtDate = new \DateTimeImmutable($generatedAt);
        } catch (\Throwable) {
            $errors[] = sprintf('generatedAt is invalid: %s', $generatedAt);

            return;
        }

        if ((time() - $generatedAtDate->getTimestamp()) > $maxAgeSeconds) {
            $errors[] = sprintf('Runtime scope lock is older than %d seconds.', $maxAgeSeconds);
        }
    }
}
