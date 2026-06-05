<?php

declare(strict_types=1);

namespace App\Administering\Contract\Accessing;

use App\Administering\Contract\AdministrationComponentIntegrationContractInterface;

/**
 * Placeholder: Accessing integration contract.
 *
 * When the Accessing component is ready to own its contract, it should:
 *
 * 1. Create App\Accessing\Contract\AccessingIntegrationContract (readonly DTO)
 *    with fields: owns, subjectPrefix, etc. — mirroring
 *    component.yaml integrations.accessing.
 *
 * 2. Create App\Accessing\Provider\Configuration\AccessingConfigurationToolProvider
 *    implementing AdministrationOwnerConfigurationToolProviderInterface,
 *    with an integrationContract(): AccessingIntegrationContract method.
 *
 * 3. Replace this stub with a thin adapter (like Rolling's).
 *
 * 4. Remove the 'accessing' key from Administering's component.yaml integrations.
 *
 * Until then, this stub allows the registry to exist without Accessing.
 *
 * Tag: administering.component_integration_contract
 */
final class AdministrationAccessingComponentIntegrationContractStub implements AdministrationComponentIntegrationContractInterface
{
    public function componentKey(): string
    {
        return 'accessing';
    }

    /**
     * Returns a minimal anonymous contract object.
     * Replace with AccessingIntegrationContract when Accessing is ready.
     *
     * @return object{owns: string, subjectPrefix: string}
     */
    public function contract(): object
    {
        return new readonly class('authentication_session_identity', 'accessing:account:') {
            public function __construct(
                public string $owns,
                public string $subjectPrefix,
            ) {
            }
        };
    }
}
