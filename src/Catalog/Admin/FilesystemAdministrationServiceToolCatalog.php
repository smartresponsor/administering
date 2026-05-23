<?php

declare(strict_types=1);

namespace App\Administering\Catalog\Admin;

use App\Administering\CatalogInterface\Admin\AdministrationServiceSectionCatalogInterface;
use App\Administering\CatalogInterface\Admin\AdministrationServiceToolCatalogInterface;
use App\Administering\CatalogInterface\Admin\AdministrationServiceToolScreenCatalogInterface;
use App\Administering\Value\Admin\AdministrationServiceTool;

/**
 * Builds the canonical tool catalog from src/Service/<Section> implementation files.
 */
final readonly class FilesystemAdministrationServiceToolCatalog implements AdministrationServiceToolCatalogInterface
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
            $shortName = basename($file, '.php');
            $screen = $this->screenCatalog->screenForTool($section, $shortName);
            $tools[] = new AdministrationServiceTool(
                section: $section,
                serviceClass: 'App\\Administering\\Service\\'.$section.'\\'.$shortName,
                shortName: $shortName,
                label: $this->labelFromClassName($shortName),
                kind: $this->kindFromClassName($shortName),
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

    private function labelFromClassName(string $className): string
    {
        $label = preg_replace('/(?<!^)[A-Z]/', ' $0', $className) ?? $className;
        $label = str_replace(' Administration ', ' ', $label);

        return trim($label);
    }

    private function kindFromClassName(string $className): string
    {
        foreach (['Provider', 'Service', 'Recorder', 'Runner', 'Factory', 'Submitter', 'Queue', 'Scanner', 'Operator', 'Writer'] as $suffix) {
            if (str_ends_with($className, $suffix)) {
                return strtolower($suffix);
            }
        }

        return 'service';
    }
}
