<?php

declare(strict_types=1);

namespace App\Administering\Service\RuntimeScope;

final readonly class AdministrationRuntimeScopeComposerInventoryReader
{
    /** @return array<string, true> */
    public function packages(string $composerPath): array
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
                if (is_string($package) && '' !== $package) {
                    $packages[$package] = true;
                }
            }
        }

        return $packages;
    }
}
