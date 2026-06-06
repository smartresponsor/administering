<?php

declare(strict_types=1);

namespace App\Administering\Service\RuntimeScope;

use App\Administering\Resolver\RuntimeScope\AdministrationRuntimeScopePathResolver;
use App\Administering\Scanner\RuntimeScope\AdministrationRuntimeScopeConfigLeakScanner;
use App\Administering\Value\RuntimeScope\AdministrationRuntimeScopeValidationResult;

final readonly class AdministrationRuntimeScopeValidationService
{
    public function __construct(
        private AdministrationRuntimeScopePathResolver $pathResolver,
        private AdministrationRuntimeScopeConfigLeakScanner $configLeakScanner,
        private AdministrationRuntimeScopeLockNormalizer $lockNormalizer,
    ) {
    }

    public function validate(string $hostDir, string $environment, int $maxAgeSeconds): AdministrationRuntimeScopeValidationResult
    {
        $hostDir = $this->pathResolver->absolutePath($hostDir);
        $lockFile = $this->pathResolver->lockPath($hostDir, $environment);
        $composerFile = $this->pathResolver->composerFile($environment);
        $composerPath = $hostDir.'/'.$composerFile;
        $lockEvidence = $this->lockNormalizer->normalize($lockFile);

        $errors = $lockEvidence->errors;
        $warnings = $lockEvidence->warnings;

        if (!is_file($composerPath)) {
            $errors[] = sprintf('Composer inventory is missing: %s', $composerPath);
        } else {
            $this->validateComposerFingerprint($lockEvidence->sourceComposerFile, $lockEvidence->sourceComposerSha256, $composerFile, $composerPath, $errors);
        }

        $errors = [...$errors, ...$lockEvidence->ageErrors($maxAgeSeconds)];
        $this->validateConfigLeaks($hostDir, $lockEvidence->disabledComponents, $errors);

        return new AdministrationRuntimeScopeValidationResult(
            $hostDir,
            $environment,
            $lockFile,
            $composerFile,
            count($lockEvidence->enabledBundleTokens),
            count($lockEvidence->disabledComponents),
            array_values(array_unique($errors)),
            array_values(array_unique($warnings)),
        );
    }

    /** @param list<string> $errors */
    private function validateComposerFingerprint(
        ?string $sourceComposerFile,
        ?string $sourceComposerSha256,
        string $composerFile,
        string $composerPath,
        array &$errors,
    ): void {
        if (null !== $sourceComposerFile && $sourceComposerFile !== $composerFile) {
            $errors[] = sprintf(
                'Runtime scope sourceComposerFile mismatch: expected %s, got %s.',
                $composerFile,
                $sourceComposerFile,
            );
        }

        $expectedSha = hash_file('sha256', $composerPath) ?: null;
        if (null !== $sourceComposerSha256 && $sourceComposerSha256 !== $expectedSha) {
            $errors[] = sprintf('Runtime scope composer fingerprint is stale for %s.', $composerFile);
        }
    }

    /**
     * @param list<string> $disabledComponents
     * @param list<string> $errors
     */
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
}
