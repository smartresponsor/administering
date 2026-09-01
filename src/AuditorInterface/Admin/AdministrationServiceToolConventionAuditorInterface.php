<?php

declare(strict_types=1);

namespace App\Administering\AuditorInterface\Admin;

use App\Administering\Value\Admin\AdministrationServiceToolConventionViolation;

interface AdministrationServiceToolConventionAuditorInterface
{
    /** @return list<AdministrationServiceToolConventionViolation> */
    public function violations(?string $section = null): array;
}
