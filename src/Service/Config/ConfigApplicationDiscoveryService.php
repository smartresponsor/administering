<?php

declare(strict_types=1);

namespace App\Administering\Service\Config;

use App\Administering\Value\Config\AdministrationConfigApplicationDescriptor;
use Symfony\Component\Yaml\Yaml;

final readonly class ConfigApplicationDiscoveryService
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
            $componentManifestPath = $rootPath.'/config/component/component.yaml';
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

    private function scalarString(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }
}
