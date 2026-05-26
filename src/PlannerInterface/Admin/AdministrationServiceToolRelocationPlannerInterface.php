<?php

declare(strict_types=1);

namespace App\Administering\PlannerInterface\Admin;

use App\Administering\Value\Admin\AdministrationServiceToolRelocationPlanEntry;

interface AdministrationServiceToolRelocationPlannerInterface
{
    /** @return list<AdministrationServiceToolRelocationPlanEntry> */
    public function plan(?string $section = null): array;
}
