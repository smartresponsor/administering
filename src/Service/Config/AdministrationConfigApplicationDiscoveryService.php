<?php

declare(strict_types=1);

namespace App\Administering\Service\Config;

use App\Administering\Value\Config\AdministrationConfigApplicationDescriptor;
use Symfony\Component\Yaml\Yaml;

final readonly class AdministrationConfigApplicationDiscoveryService
{
    /**
     * @param list<string> $connectedComponents
     */
    public function __construct(
        private string $projectDir,
        private array $connectedComponents = [],
    ) {
    }

    /** @return list<AdministrationConfigApplicationDescriptor> */
    public function discover(): array
    {
        $descriptors = [];
        foreach (array_values(array_unique(array_map(static fn (string $componentName): string => trim($componentName), $this->connectedComponents))) as $componentName) {
            if ('' === $componentName) {
                continue;
            }

            $rootPath = $this->componentRootPath($componentName);
            $componentManifestPath = $this->componentManifestPath($rootPath);
            if (null === $componentManifestPath) {
                continue;
            }
            if (!is_file($componentManifestPath)) {
                continue;
            }

            $parsed = Yaml::parseFile($componentManifestPath);
            if (!is_array($parsed)) {
                continue;
            }

            $label = $this->scalarString($parsed['ui_label'] ?? null)
                ?? $this->scalarString($parsed['title'] ?? null)
                ?? $componentName;

            $applicationCode = $this->scalarString($parsed['component'] ?? null) ?? $componentName;
            $descriptors[$applicationCode] = new AdministrationConfigApplicationDescriptor(
                applicationCode: $applicationCode,
                label: $label,
                rootPath: $rootPath,
                manifestPath: $componentManifestPath,
                checksum: hash_file('sha256', $componentManifestPath) ?: '',
                enabled: true,
                metadata: [
                    'package' => $this->scalarString($parsed['package'] ?? null),
                    'status' => $this->scalarString($parsed['status'] ?? null),
                    'namespace' => $this->scalarString($parsed['namespace'] ?? null),
                    'ui_label' => $this->scalarString($parsed['ui_label'] ?? null),
                ],
            );
        }

        return array_values($descriptors);
    }

    private function componentRootPath(string $componentName): string
    {
        return rtrim($this->projectDir, '/\\').'/../'.$componentName;
    }

    private function componentManifestPath(string $rootPath): ?string
    {
        $composerPath = $rootPath.'/composer.json';
        if (!is_file($composerPath)) {
            return null;
        }

        try {
            $composer = json_decode((string) file_get_contents($composerPath), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (!is_array($composer) || !is_string($composer['name'] ?? null)) {
            return null;
        }

        $nameParts = explode('/', $composer['name'], 2);
        if (2 !== count($nameParts) || '' === trim($nameParts[1])) {
            return null;
        }

        $subjectPrefix = str_replace('-', '_', strtolower(trim($nameParts[1])));

        return $rootPath.'/config/component/'.$subjectPrefix.'_component.yaml';
    }

    private function scalarString(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }
}
