<?php

declare(strict_types=1);

namespace App\Administering\Catalog\Admin;

use App\Administering\CatalogInterface\Admin\AdministrationServiceSectionCatalogInterface;
use App\Administering\Controller\Admin\Crud\AdministrationServiceSectionRecordCrudController;
use App\Administering\Controller\Admin\Crud\AdministrationServiceToolRecordCrudController;
use App\Administering\Value\Admin\AdministrationServiceSection;

/**
 * Canonical service-section catalog for the Administering left menu.
 *
 * A section appears in the sidebar when it is represented by a matching
 * src/Service/<Section> directory. Empty section directories are valid planned
 * sections: service files define tools, but directory existence defines the menu root.
 */
final class AdministrationServiceSectionCatalog implements AdministrationServiceSectionCatalogInterface
{
    /** @return list<AdministrationServiceSection> */
    public function sections(): array
    {
        $directories = $this->serviceSectionDirectories();
        $sections = [];

        foreach ($this->knownSections() as $key => $metadata) {
            if (in_array($key, $directories, true)) {
                $sections[] = $this->sectionFromMetadata($key, $metadata);
            }
        }

        foreach ($directories as $key) {
            if (!array_key_exists($key, $this->knownSections())) {
                $sections[] = $this->plannedSection($key);
            }
        }

        return $sections;
    }

    /** @return array<string, array{label:string, icon:string, crud:class-string, permission:string}> */
    private function knownSections(): array
    {
        return [
            'Rolling' => [
                'label' => 'Rolling',
                'icon' => 'fa fa-user-shield',
                'crud' => AdministrationServiceToolRecordCrudController::class,
                'permission' => 'administration.rolling.subject_access_report.view',
            ],
            'Accessing' => [
                'label' => 'Accessing',
                'icon' => 'fa fa-user-lock',
                'crud' => AdministrationServiceToolRecordCrudController::class,
                'permission' => 'administration.accessing.account_action.audit.view',
            ],
            'Managing' => [
                'label' => 'Managing',
                'icon' => 'fa fa-table-list',
                'crud' => AdministrationServiceToolRecordCrudController::class,
                'permission' => 'administration.rolling.permission_catalog.view',
            ],
            'Symfony' => [
                'label' => 'Symfony',
                'icon' => 'fa fa-route',
                'crud' => AdministrationServiceToolRecordCrudController::class,
                'permission' => 'administration.config.view',
            ],
            'Environment' => [
                'label' => 'Environment',
                'icon' => 'fa fa-server',
                'crud' => AdministrationServiceToolRecordCrudController::class,
                'permission' => 'administration.config.view',
            ],
            'Connected' => [
                'label' => 'Connected Components',
                'icon' => 'fa fa-layer-group',
                'crud' => AdministrationServiceToolRecordCrudController::class,
                'permission' => 'administration.connected_component.overview.view',
            ],
            'Operation' => [
                'label' => 'Operations',
                'icon' => 'fa fa-list-check',
                'crud' => AdministrationServiceToolRecordCrudController::class,
                'permission' => 'administration.operation.view',
            ],
            'Configuration' => [
                'label' => 'Configuration',
                'icon' => 'fa fa-file-code',
                'crud' => AdministrationServiceToolRecordCrudController::class,
                'permission' => 'administration.config.view',
            ],
            'Credential' => [
                'label' => 'Credentials',
                'icon' => 'fa fa-key',
                'crud' => AdministrationServiceToolRecordCrudController::class,
                'permission' => 'administration.config.view',
            ],
            'Audit' => [
                'label' => 'Audit',
                'icon' => 'fa fa-clock-rotate-left',
                'crud' => AdministrationServiceToolRecordCrudController::class,
                'permission' => 'administration.accessing.account_action.audit.view',
            ],
        ];
    }

    /** @param array{label:string, icon:string, crud:class-string, permission:string} $metadata */
    private function sectionFromMetadata(string $key, array $metadata): AdministrationServiceSection
    {
        return new AdministrationServiceSection($key, $metadata['label'], $metadata['icon'], 'src/Service/'.$key, $metadata['crud'], $metadata['permission']);
    }

    private function plannedSection(string $key): AdministrationServiceSection
    {
        return new AdministrationServiceSection(
            $key,
            $this->labelFromKey($key),
            'fa fa-folder-tree',
            'src/Service/'.$key,
            AdministrationServiceSectionRecordCrudController::class,
            'administration.dashboard.view',
        );
    }

    /** @return list<string> */
    private function serviceSectionDirectories(): array
    {
        $root = dirname(__DIR__, 2).'/Service';
        if (!is_dir($root)) {
            return [];
        }

        $directories = array_values(array_filter(
            scandir($root) ?: [],
            static fn (string $entry): bool => !str_starts_with($entry, '.') && is_dir($root.'/'.$entry) && 'Config' !== $entry,
        ));
        sort($directories);

        return $directories;
    }

    private function labelFromKey(string $key): string
    {
        $label = preg_replace('/(?<!^)[A-Z]/', ' $0', $key) ?? $key;

        return trim($label);
    }
}
