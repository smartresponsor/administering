<?php

declare(strict_types=1);

namespace App\Administering\Reader\RuntimeScope;

use App\Administering\Value\RuntimeScope\AdministrationRuntimeScopeComposerInventoryEvidence;
use App\Administering\Value\RuntimeScope\AdministrationRuntimeScopeVisibility;

final readonly class AdministrationRuntimeScopeComposerInventoryReader
{
    /** @return array<string, true> */
    public function packages(string $composerPath): array
    {
        return $this->inventory($composerPath, ['components' => []])->packages;
    }

    /**
     * @param array{components: array<string, array{package: string, bundleToken: string}>} $catalog
     */
    public function inventory(string $composerPath, array $catalog): AdministrationRuntimeScopeComposerInventoryEvidence
    {
        $packages = $this->readPackages($composerPath);
        $componentPackages = [];
        $catalogPackages = [];

        foreach ($catalog['components'] as $component => $definition) {
            $package = $definition['package'];
            if ('' === trim($package)) {
                continue;
            }

            $componentKey = AdministrationRuntimeScopeVisibility::normalizeComponent($component);
            $package = strtolower(trim($package));
            $catalogPackages[$package] = $componentKey;

            if (isset($packages[$package])) {
                $componentPackages[$componentKey] = $package;
            }
        }

        ksort($componentPackages);

        $ignoredRuntimeScopePackages = [];
        foreach (array_keys($packages) as $package) {
            if (isset($catalogPackages[$package])) {
                continue;
            }

            if ($this->looksLikeRuntimeScopePackage($package)) {
                $ignoredRuntimeScopePackages[] = $package;
            }
        }
        sort($ignoredRuntimeScopePackages);

        return new AdministrationRuntimeScopeComposerInventoryEvidence(
            composerPath: $composerPath,
            packages: $packages,
            componentPackages: $componentPackages,
            ignoredRuntimeScopePackages: $ignoredRuntimeScopePackages,
        );
    }

    /** @return array<string, true> */
    private function readPackages(string $composerPath): array
    {
        try {
            $payload = json_decode((string) file_get_contents($composerPath), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $exception) {
            throw new \RuntimeException(sprintf('Unable to read composer inventory %s: %s', $composerPath, $exception->getMessage()));
        }

        if (!is_array($payload)) {
            return [];
        }

        $packages = [];
        foreach (['require', 'require-dev'] as $section) {
            if (!is_array($payload[$section] ?? null)) {
                continue;
            }

            foreach (array_keys($payload[$section]) as $package) {
                if (is_string($package) && '' !== trim($package)) {
                    $packages[strtolower(trim($package))] = true;
                }
            }
        }
        ksort($packages);

        return $packages;
    }

    private function looksLikeRuntimeScopePackage(string $package): bool
    {
        if (!str_contains($package, '/')) {
            return false;
        }

        [$vendor] = explode('/', $package, 2);

        return !in_array($vendor, [
            'doctrine',
            'easycorp',
            'fakerphp',
            'friendsofphp',
            'lexik',
            'nelmio',
            'phpstan',
            'phpunit',
            'scheb',
            'symfony',
            'symfonycasts',
            'twig',
        ], true);
    }
}
