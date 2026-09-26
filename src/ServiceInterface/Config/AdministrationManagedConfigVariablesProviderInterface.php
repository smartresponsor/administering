<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Config;

use App\Administering\Value\Config\AdministrationConfigVariable;

interface AdministrationManagedConfigVariablesProviderInterface
{
    /** @return iterable<AdministrationConfigVariable> */
    public function variables(): iterable;
}
