<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Configuration;

use App\Administering\Value\Configuration\AdministrationConfigurationScanResult;

interface AdministrationConfigurationScannerInterface
{
    public function scan(string $hostRoot): AdministrationConfigurationScanResult;
}
