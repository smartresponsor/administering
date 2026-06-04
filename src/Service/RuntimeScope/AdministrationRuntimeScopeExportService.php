<?php

declare(strict_types=1);

namespace App\Administering\Service\RuntimeScope;

use App\Administering\Factory\RuntimeScope\AdministrationRuntimeScopePhpLockSourceFactory;
use App\Administering\Value\RuntimeScope\AdministrationRuntimeScopeExportRequest;
use App\Administering\Value\RuntimeScope\AdministrationRuntimeScopeExportResult;

final readonly class AdministrationRuntimeScopeExportService
{
    public function __construct(
        private AdministrationRuntimeScopePathResolver $pathResolver,
        private AdministrationRuntimeScopeBundleCatalogReader $catalogReader,
        private AdministrationRuntimeScopeComposerInventoryReader $composerInventoryReader,
        private AdministrationRuntimeScopePhpLockSourceFactory $sourceFactory,
    ) {
    }

    public function export(AdministrationRuntimeScopeExportRequest $request): AdministrationRuntimeScopeExportResult
    {
        $hostDir = $this->pathResolver->absolutePath($request->hostDir);
        $catalogFile = $this->pathResolver->absolutePath($request->catalogFile);
        $composerFile = $this->pathResolver->composerFile($request->environment);
        $composerPath = $hostDir.'/'.$composerFile;

        if (!is_file($composerPath)) {
            throw new \RuntimeException(sprintf('Composer inventory is missing: %s', $composerPath));
        }

        $catalog = $this->catalogReader->catalog($catalogFile);
        $composerPackages = $this->composerInventoryReader->packages($composerPath);
        $payload = $this->buildPayload(
            $catalog,
            $composerPackages,
            $composerPath,
            $composerFile,
            $request,
        );
        $lockPath = $this->pathResolver->lockPath($hostDir, $request->environment);
        $source = $this->sourceFactory->source($payload);

        if (!$request->dryRun) {
            $directory = dirname($lockPath);
            if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
                throw new \RuntimeException(sprintf('Unable to create lock directory: %s', $directory));
            }

            file_put_contents($lockPath, $source);
        }

        return new AdministrationRuntimeScopeExportResult($lockPath, $source, $payload);
    }

    /**
     * @param array{components: array<string, array{package: string, bundle: string}>} $catalog
     * @param array<string, true>                                                      $composerPackages
     *
     * @return array<string, mixed>
     */
    private function buildPayload(
        array $catalog,
        array $composerPackages,
        string $composerPath,
        string $composerFile,
        AdministrationRuntimeScopeExportRequest $request,
    ): array {
        $components = $catalog['components'];
        $unknownOverrides = array_values(array_diff(array_merge($request->forceEnable, $request->forceDisable), array_keys($components)));
        if ([] !== $unknownOverrides) {
            throw new \RuntimeException(sprintf('Unknown runtime-scope component override(s): %s', implode(', ', $unknownOverrides)));
        }

        $enabledComponents = [];
        $disabledComponents = [];
        $skippedComponents = [];
        $missingPackages = [];

        foreach ($components as $component => $definition) {
            $package = $definition['package'];
            $installed = isset($composerPackages[$package]);
            $forcedEnabled = in_array($component, $request->forceEnable, true);
            $forcedDisabled = in_array($component, $request->forceDisable, true);

            if ($forcedDisabled) {
                $disabledComponents[] = $component;
                continue;
            }

            if ($installed || $forcedEnabled) {
                if (!$installed && $forcedEnabled) {
                    if (!$request->skipMissingPackages) {
                        $missingPackages[] = sprintf('%s (%s)', $component, $package);
                        continue;
                    }

                    $skippedComponents[] = $component;
                    $disabledComponents[] = $component;
                    continue;
                }

                $enabledComponents[] = $component;
                continue;
            }

            $disabledComponents[] = $component;
        }

        if ([] !== $missingPackages) {
            throw new \RuntimeException(sprintf('Forced enabled component package is missing from %s: %s. Re-run with --skip-missing-packages to skip it.', $composerFile, implode(', ', $missingPackages)));
        }

        $enabledBundles = [];
        foreach ($enabledComponents as $component) {
            $enabledBundles[] = $components[$component]['bundle'];
        }

        return [
            'schema' => 'app.kernel.runtime_scope.v1',
            'scope' => $request->scope,
            'environment' => $request->environment,
            'source' => 'materialized by administering:runtime-scope:export from composer inventory and Administering-owned bundle catalog',
            'sourceComposerFile' => $composerFile,
            'sourceComposerSha256' => hash_file('sha256', $composerPath) ?: null,
            'sourceComposerPackageCount' => count(array_filter(array_keys($composerPackages), static fn (string $package): bool => 'php' !== $package)),
            'strict' => $request->strict,
            'generatedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'generatedBy' => 'administering:runtime-scope:export',
            'enabledComponents' => array_values($enabledComponents),
            'enabledBundles' => array_values($enabledBundles),
            'disabledComponents' => array_values(array_unique($disabledComponents)),
            'skippedComponents' => array_values(array_unique($skippedComponents)),
        ];
    }
}
