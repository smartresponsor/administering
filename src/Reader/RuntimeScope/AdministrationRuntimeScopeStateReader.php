<?php

declare(strict_types=1);

namespace App\Administering\Reader\RuntimeScope;

use App\Administering\Resolver\RuntimeScope\AdministrationRuntimeScopePathResolver;
use App\Administering\Service\RuntimeScope\AdministrationRuntimeScopeLockNormalizer;
use App\Administering\Value\RuntimeScope\AdministrationRuntimeScopeState;

final readonly class AdministrationRuntimeScopeStateReader
{
    public function __construct(
        private AdministrationRuntimeScopePathResolver $pathResolver,
        private AdministrationRuntimeScopeComposerInventoryReader $composerInventoryReader,
        private AdministrationRuntimeScopeBundleCatalogReader $catalogReader,
        private AdministrationRuntimeScopeLockNormalizer $lockNormalizer,
    ) {
    }

    public function read(string $hostDir, string $environment): AdministrationRuntimeScopeState
    {
        $hostDir = $this->pathResolver->absolutePath($hostDir);
        $composerFile = $this->pathResolver->composerFile($environment);
        $composerPath = rtrim($hostDir, '/\\').'/'.$composerFile;
        $lockPath = $this->pathResolver->lockPath($hostDir, $environment);
        $catalogPath = $this->pathResolver->bundleCatalogPath();
        $sourceErrors = [];

        $catalog = ['components' => []];
        try {
            $catalog = $this->catalogReader->catalog($catalogPath);
        } catch (\Throwable $exception) {
            $sourceErrors[] = $exception->getMessage();
        }

        $composerPackages = [];
        $composerComponentPackages = [];
        $ignoredRuntimeScopePackages = [];
        if (!is_file($composerPath)) {
            $sourceErrors[] = sprintf('Composer inventory is missing: %s', $composerPath);
        } else {
            try {
                $composerInventory = $this->composerInventoryReader->inventory($composerPath, $catalog);
                $composerPackages = $composerInventory->packages;
                $composerComponentPackages = $composerInventory->componentPackages;
                $ignoredRuntimeScopePackages = $composerInventory->ignoredRuntimeScopePackages;
            } catch (\Throwable $exception) {
                $sourceErrors[] = $exception->getMessage();
            }
        }

        $lockEvidence = $this->lockNormalizer->normalize($lockPath);
        $sourceErrors = [...$sourceErrors, ...$lockEvidence->errors];

        if ([] !== $ignoredRuntimeScopePackages) {
            $sourceErrors[] = sprintf(
                'Composer inventory contains runtime-scope-like packages absent from Administering token catalog: %s',
                implode(', ', $ignoredRuntimeScopePackages),
            );
        }

        $installedComponents = array_keys($composerComponentPackages);

        return new AdministrationRuntimeScopeState(
            hostDir: $hostDir,
            environment: $environment,
            composerFile: $composerFile,
            composerPath: $composerPath,
            composerPackages: $composerPackages,
            composerComponentPackages: $composerComponentPackages,
            appRuntimeScopeRaw: null,
            appRuntimeScope: [],
            lockPath: $lockPath,
            lockPresent: $lockEvidence->present && $lockEvidence->isValid(),
            enabledBundleTokens: $lockEvidence->enabledBundleTokens,
            enabledComponents: $lockEvidence->enabledComponents,
            disabledComponents: $lockEvidence->disabledComponents,
            installedComponents: $installedComponents,
            sourceErrors: $sourceErrors,
        );
    }
}
