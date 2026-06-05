<?php

declare(strict_types=1);

namespace App\Administering\Contract\Rolling;

use App\Administering\Contract\AdministrationComponentIntegrationContractInterface;
use App\Rolling\Contract\RollingIntegrationContract;
use App\Rolling\Provider\Configuration\RollingConfigurationToolProvider;

/**
 * Adapter: bridges RollingConfigurationToolProvider → AdministrationComponentIntegrationContractRegistry.
 *
 * Lives in Administering because it is the glue between Administering's
 * generic registry and Rolling's typed contract. Rolling owns the data;
 * this class is ~10 lines of routing.
 *
 * Tag: administering.component_integration_contract
 */
final class AdministrationRollingComponentIntegrationContractProvider implements AdministrationComponentIntegrationContractInterface
{
    public function __construct(
        private readonly RollingConfigurationToolProvider $rollingProvider,
    ) {
    }

    public function componentKey(): string
    {
        return 'rolling';
    }

    public function contract(): RollingIntegrationContract
    {
        return $this->rollingProvider->integrationContract();
    }
}
