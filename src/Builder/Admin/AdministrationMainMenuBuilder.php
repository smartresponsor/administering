<?php

declare(strict_types=1);

namespace App\Administering\Builder\Admin;

use App\Administering\BuilderInterface\Admin\AdministrationMainMenuBuilderInterface;
use App\Administering\Entity\AdministrationConnectedComponentRecord;
use App\Administering\Entity\AdministrationServiceToolRecord;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;

/**
 * Builds the primary EasyAdmin menu around operator-facing indexes.
 *
 * The menu starts from concrete repository/admin facts: tools discovered from
 * src/Service and enabled components derived from APP_RUNTIME_SCOPE lock
 * evidence. Low-level source readers stay reachable through their surfaces,
 * but they are not the primary admin navigation.
 */
final readonly class AdministrationMainMenuBuilder implements AdministrationMainMenuBuilderInterface
{
    public function build(): iterable
    {
        yield MenuItem::linkToDashboard('Home', 'fa fa-home')
            ->setPermission('administration.dashboard.view');

        yield MenuItem::linkToCrud('Tools', 'fa fa-toolbox', AdministrationServiceToolRecord::class)
            ->setPermission('administration.dashboard.view');

        yield MenuItem::linkToRoute('Commands', 'fa fa-terminal', 'administration_command_index')
            ->setPermission('administration.dashboard.view');

        yield MenuItem::linkToCrud('Enabled Components', 'fa fa-toggle-on', AdministrationConnectedComponentRecord::class)
            ->setPermission('administration.connected_component.overview.view');
    }
}
