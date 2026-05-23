<?php

declare(strict_types=1);

namespace App\Administering\ProviderInterface\Admin;

use App\Administering\Value\Admin\AdministrationServiceSectionToolDashboard;
use App\Administering\Value\Admin\AdministrationServiceToolDetail;

interface AdministrationServiceSectionToolDashboardProviderInterface
{
    public function dashboardForSection(string $sectionKey): ?AdministrationServiceSectionToolDashboard;

    public function detailForTool(string $sectionKey, string $toolShortName): ?AdministrationServiceToolDetail;
}
