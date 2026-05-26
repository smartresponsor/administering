<?php

declare(strict_types=1);

namespace App\Administering\ProviderInterface\Admin;

use App\Administering\Value\Admin\AdministrationServiceToolMenuSection;

interface AdministrationServiceToolMenuSectionProviderInterface
{
    /** @return list<AdministrationServiceToolMenuSection> */
    public function menuSections(): array;
}
