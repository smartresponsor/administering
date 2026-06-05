<?php

declare(strict_types=1);

namespace App\Administering\Reader\RuntimeScope;

final readonly class AdministrationRuntimeScopeBundleCatalogReader
{
    /** @return array{components: array<string, array{package: string, bundle: string}>} */
    public function catalog(string $catalogPath): array
    {
        if (!is_file($catalogPath)) {
            throw new \RuntimeException(sprintf('Runtime-scope bundle catalog is missing: %s', $catalogPath));
        }

        $payload = require $catalogPath;
        if (!is_array($payload) || !is_array($payload['components'] ?? null)) {
            throw new \RuntimeException(sprintf('Runtime-scope bundle catalog must return an array with components: %s', $catalogPath));
        }

        $components = [];
        foreach ($payload['components'] as $component => $definition) {
            if (!is_string($component) || !is_array($definition)) {
                continue;
            }

            $package = $definition['package'] ?? null;
            $bundle = $definition['bundle'] ?? null;
            if (!is_string($package) || '' === $package || !is_string($bundle) || '' === $bundle) {
                throw new \RuntimeException(sprintf('Invalid runtime-scope catalog entry for component: %s', $component));
            }

            $components[strtolower($component)] = ['package' => $package, 'bundle' => $bundle];
        }

        ksort($components);

        return ['components' => $components];
    }
}
