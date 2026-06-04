<?php

declare(strict_types=1);

namespace App\Administering\Contract\Accessing;

use App\Accessing\Contract\AccessIntegrationContract;
use App\Accessing\Provider\Configuration\AccessingConfigurationToolProvider;
use App\Administering\Contract\ComponentIntegrationContractInterface;

/**
 * Adapter: bridges AccessingConfigurationToolProvider → ComponentIntegrationContractRegistry.
 *
 * Replaces AccessingComponentIntegrationContractProvider stub.
 * Accessing now owns its contract; this class is the thin routing layer.
 *
 * Tag: administering.component_integration_contract
 */
final class AccessingComponentIntegrationContractProvider implements ComponentIntegrationContractInterface
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
