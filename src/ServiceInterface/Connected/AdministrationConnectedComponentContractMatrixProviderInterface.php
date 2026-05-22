<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Connected;

use App\Administering\Value\Connected\AdministrationConnectedComponentContractMatrix;

interface AdministrationConnectedComponentContractMatrixProviderInterface
{
    public function matrix(): AdministrationConnectedComponentContractMatrix;
}
