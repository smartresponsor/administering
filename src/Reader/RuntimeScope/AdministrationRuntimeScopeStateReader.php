<?php

declare(strict_types=1);

namespace App\Administering\Reader\RuntimeScope;

use App\Administering\Resolver\RuntimeScope\AdministrationRuntimeScopePathResolver;
use App\Administering\Service\RuntimeScope\AdministrationRuntimeScopeLockService;
use App\Administering\Value\RuntimeScope\AdministrationRuntimeScopeState;

final readonly class AdministrationRuntimeScopeStateReader
{
    public function __construct(
        private AdministrationRuntimeScopePathResolver $pathResolver,
        private AdministrationRuntimeScopeComposerInventoryReader $composerInventoryReader,
        private AdministrationRuntimeScopeBundleCatalogReader $catalogReader,
        private AdministrationRuntimeScopeLockService $lockNormalizer,
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

        $catalog = $this->readCatalog($catalogPath, $sourceErrors);
        [$composerPackages, $composerComponentPackages, $ignoredRuntimeScopePackages] = $this->readComposerInventory($composerPath, $catalog, $sourceErrors);

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

    /**
     * @param list<string> $sourceErrors
     *
     * @return array{components: array<string, array{package: string, bundleToken: string}>}
     */
    private function readCatalog(string $catalogPath, array &$sourceErrors): array
    {
        try {
            return $this->catalogReader->catalog($catalogPath);
        } catch (\Throwable $exception) {
            $sourceErrors[] = $exception->getMessage();

            return ['components' => []];
        }
    }

    /**
     * @param array{components: array<string, array{package: string, bundleToken: string}>} $catalog
     * @param list<string>                                                                  $sourceErrors
     *
     * @return array{0: array<string, true>, 1: array<string, string>, 2: list<string>}
     */
    private function readComposerInventory(string $composerPath, array $catalog, array &$sourceErrors): array
    {
        if (!is_file($composerPath)) {
            $sourceErrors[] = sprintf('Composer inventory is missing: %s', $composerPath);

            return [[], [], []];
        }

        try {
            $composerInventory = $this->composerInventoryReader->inventory($composerPath, $catalog);

            return [
                $composerInventory->packages,
                $composerInventory->componentPackages,
                $composerInventory->ignoredRuntimeScopePackages,
            ];
        } catch (\Throwable $exception) {
            $sourceErrors[] = $exception->getMessage();

            return [[], [], []];
        }
    }
}
