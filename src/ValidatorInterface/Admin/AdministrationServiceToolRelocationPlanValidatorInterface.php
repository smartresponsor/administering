<?php

declare(strict_types=1);

namespace App\Administering\ValidatorInterface\Admin;

use App\Administering\Value\Admin\AdministrationServiceToolRelocationPlanEntry;
use App\Administering\Value\Admin\AdministrationServiceToolRelocationPlanIssue;

interface AdministrationServiceToolRelocationPlanValidatorInterface
{
    /**
     * @param list<AdministrationServiceToolRelocationPlanEntry> $entries
     *
     * @return list<AdministrationServiceToolRelocationPlanIssue>
     */
    public function validate(array $entries): array;
}
