<?php

declare(strict_types=1);

namespace App\Administering\Builder\Admin;

use App\Administering\BuilderInterface\Admin\AdministrationMainMenuBuilderInterface;
use App\Administering\CatalogInterface\Admin\AdministrationServiceSectionCatalogInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;

/**
 * Builds the left EasyAdmin menu from the canonical service-section catalog.
 */
final readonly class AdministrationMainMenuBuilder implements AdministrationMainMenuBuilderInterface
{
    public function __construct(private AdministrationServiceSectionCatalogInterface $sectionCatalog)
    {
    }

    public function build(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home')
            ->setPermission('administration.dashboard.view');

        foreach ($this->sectionCatalog->sections() as $section) {
            yield MenuItem::linkToRoute($section->label, $section->icon, 'administration_service_section_tools', [
                'sectionKey' => $section->key,
            ])
                ->setPermission($section->permission);
        }
    }
}
