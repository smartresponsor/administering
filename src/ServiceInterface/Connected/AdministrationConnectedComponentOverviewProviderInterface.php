<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Connected;

use App\Administering\Value\Connected\AdministrationConnectedComponentOverview;

interface AdministrationConnectedComponentOverviewProviderInterface
{
    public function overview(): AdministrationConnectedComponentOverview;
}
