<?php

declare(strict_types=1);

namespace App\Administering\ProviderInterface\Admin;

use App\Administering\Value\Admin\AdministrationServiceToolIndexReadinessReport;

interface AdministrationServiceToolIndexReadinessProviderInterface
{
    public function report(?string $sectionFilter = null): AdministrationServiceToolIndexReadinessReport;
}
