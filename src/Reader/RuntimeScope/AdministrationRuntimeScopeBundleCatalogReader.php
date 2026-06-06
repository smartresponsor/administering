<?php

declare(strict_types=1);

namespace App\Administering\Reader\RuntimeScope;

final readonly class AdministrationRuntimeScopeBundleCatalogReader
{
    /** @return array{components: array<string, array{package: string, bundleToken: string}>} */
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
            $bundleToken = $definition['bundle_token'] ?? null;
            if (!is_string($package) || '' === trim($package) || !is_string($bundleToken) || '' === trim($bundleToken)) {
                throw new \RuntimeException(sprintf('Invalid runtime-scope catalog entry for component: %s', $component));
            }

            if (str_contains($bundleToken, '\\')) {
                throw new \RuntimeException(sprintf('Runtime-scope bundle token must not be a PHP class name: %s', $component));
            }

            $components[strtolower($component)] = [
                'package' => trim($package),
                'bundleToken' => strtolower(trim($bundleToken)),
            ];
        }

        ksort($components);

        return ['components' => $components];
    }
}
