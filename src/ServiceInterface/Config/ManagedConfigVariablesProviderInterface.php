<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Config;

use App\Administering\Value\Config\ConfigVariable;

interface ManagedConfigVariablesProviderInterface
{
    /** @return iterable<ConfigVariable> */
    public function variables(): iterable;
}
