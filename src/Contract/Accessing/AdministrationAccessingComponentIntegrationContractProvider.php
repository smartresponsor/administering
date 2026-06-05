<?php

declare(strict_types=1);

namespace App\Administering\Contract\Accessing;

use App\Accessing\Contract\AccessIntegrationContract;
use App\Accessing\Provider\Configuration\AccessingConfigurationToolProvider;
use App\Administering\Contract\AdministrationComponentIntegrationContractInterface;

/**
 * Adapter: bridges AccessingConfigurationToolProvider → AdministrationComponentIntegrationContractRegistry.
 *
 * Replaces AdministrationAccessingComponentIntegrationContractProvider stub.
 * Accessing now owns its contract; this class is the thin routing layer.
 *
 * Tag: administering.component_integration_contract
 */
final class AdministrationAccessingComponentIntegrationContractProvider implements AdministrationComponentIntegrationContractInterface
{
    public function __construct(
        private readonly AccessingConfigurationToolProvider $accessingProvider,
    ) {
    }

    public function componentKey(): string
    {
        return 'accessing';
    }

    public function contract(): AccessIntegrationContract
    {
        return $this->accessingProvider->integrationContract();
    }
}
