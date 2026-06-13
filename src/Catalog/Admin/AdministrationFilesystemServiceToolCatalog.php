<?php

declare(strict_types=1);

namespace App\Administering\Catalog\Admin;

use App\Administering\CatalogInterface\Admin\AdministrationServiceSectionCatalogInterface;
use App\Administering\CatalogInterface\Admin\AdministrationServiceToolCatalogInterface;
use App\Administering\CatalogInterface\Admin\AdministrationServiceToolScreenCatalogInterface;
use App\Administering\ServiceInterface\Admin\AdministrationServiceToolHandlerInterface;
use App\Administering\Value\Admin\AdministrationServiceTool;
use App\Administering\Value\Operation\AdministrationOperationType;

/**
 * Builds the canonical tool catalog from src/Service/<Direction> implementation files.
 *
 * A file is indexed only when its namespace and short nameEntity prove that it is a
 * menu/openable service tool: Administration{Direction}{ToolSlug}Service.
 */
final readonly class AdministrationFilesystemServiceToolCatalog implements AdministrationServiceToolCatalogInterface
{
    public function __construct(
        private AdministrationServiceSectionCatalogInterface $sectionCatalog,
        private AdministrationServiceToolScreenCatalogInterface $screenCatalog,
    ) {
    }

    public function tools(): array
    {
        $tools = [];

        foreach ($this->sectionCatalog->sections() as $section) {
            foreach ($this->toolsForSection($section->key) as $tool) {
                $tools[] = $tool;
            }
        }

        return $tools;
    }

    public function toolsForSection(string $section): array
    {
        $directory = $this->serviceRoot().'/'.$section;
        if (!is_dir($directory)) {
            return [];
        }

        $files = array_values(array_filter(
            scandir($directory) ?: [],
            static fn (string $file): bool => str_ends_with($file, '.php'),
        ));
        sort($files);

        $tools = [];
        foreach ($files as $file) {
            $path = $directory.'/'.$file;
            $shortName = basename($file, '.php');
            $toolSlug = $this->toolSlug($section, $shortName);
            if (null === $toolSlug) {
                continue;
            }

            $serviceClass = 'App\\Administering\\Service\\'.$section.'\\'.$shortName;
            if ($this->declaredNamespace($path) !== 'App\\Administering\\Service\\'.$section) {
                continue;
            }

            $screen = $this->screenCatalog->screenForTool($section, $shortName);
            $tools[] = new AdministrationServiceTool(
                section: $section,
                directionToken: $section,
                toolSlug: $toolSlug,
                toolKey: $this->toolKey($section, $toolSlug),
                serviceClass: $serviceClass,
                shortName: $shortName,
                serviceFile: 'src/Service/'.$section.'/'.$file,
                label: $this->labelFromToolSlug($toolSlug),
                kind: 'service',
                operationType: AdministrationOperationType::SERVICE_TOOL_LAUNCH,
                checksum: hash_file('sha256', $path) ?: hash('sha256', $serviceClass),
                formTypeClass: $this->formTypeClass($section, $toolSlug),
                formDataClass: $this->formDataClass($section, $toolSlug),
                executable: is_subclass_of($serviceClass, AdministrationServiceToolHandlerInterface::class),
                primaryRouteName: $screen?->routeName,
                primaryRouteLabel: $screen?->label,
            );
        }

        return $tools;
    }

    private function serviceRoot(): string
    {
        return dirname(__DIR__, 2).'/Service';
    }

    private function toolSlug(string $section, string $shortName): ?string
    {
        $sectionPrefix = 'Administration'.$section;
        if (str_starts_with($shortName, $sectionPrefix) && str_ends_with($shortName, 'Service')) {
            return $this->nonEmptySlug(substr($shortName, strlen($sectionPrefix), -strlen('Service')));
        }

        if (str_starts_with($shortName, 'Administration') && str_ends_with($shortName, 'Service')) {
            return $this->nonEmptySlug(substr($shortName, strlen('Administration'), -strlen('Service')));
        }

        if (str_starts_with($shortName, 'Administration')) {
            return $this->nonEmptySlug(substr($shortName, strlen('Administration')));
        }

        return null;
    }

    private function nonEmptySlug(string $slug): ?string
    {
        if ('' === $slug || !ctype_upper($slug[0])) {
            return null;
        }

        return $slug;
    }

    private function declaredNamespace(string $path): ?string
    {
        $contents = file_get_contents($path);
        if (!is_string($contents)) {
            return null;
        }

        if (1 !== preg_match('/^namespace\s+([^;]+);/m', $contents, $matches)) {
            return null;
        }

        return trim($matches[1]);
    }

    private function toolKey(string $section, string $toolSlug): string
    {
        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $section.'.'.$toolSlug));
    }

    private function labelFromToolSlug(string $toolSlug): string
    {
        $label = preg_replace('/(?<!^)[A-Z]/', ' $0', $toolSlug) ?? $toolSlug;

        return trim($label);
    }

    private function formTypeClass(string $section, string $toolSlug): ?string
    {
        foreach ($this->formTypeCandidates($section, $toolSlug) as $candidate) {
            if (class_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function formDataClass(string $section, string $toolSlug): ?string
    {
        foreach ($this->formDataClassCandidates($section, $toolSlug) as $candidate) {
            if (class_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /** @return list<string> */
    private function formDataClassCandidates(string $section, string $toolSlug): array
    {
        return [
            'App\\Administering\\Value\\Form\\'.$section.'\\Administration'.$section.$toolSlug.'Data',
        ];
    }

    /** @return list<string> */
    private function formTypeCandidates(string $section, string $toolSlug): array
    {
        return [
            'App\\Administering\\Form\\'.$section.'\\Administration'.$section.$toolSlug.'FormType',
        ];
    }
}
