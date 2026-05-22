<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Connected;

use App\Administering\Value\Connected\AdministrationConnectedComponentCapabilityMatrix;

interface AdministrationConnectedComponentCapabilityMatrixProviderInterface
{
    public function matrix(): AdministrationConnectedComponentCapabilityMatrix;
}
