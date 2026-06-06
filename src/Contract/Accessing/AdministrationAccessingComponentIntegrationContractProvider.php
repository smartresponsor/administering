<?php

declare(strict_types=1);

namespace App\Administering\Contract\Accessing;

use App\Administering\Contract\AdministrationComponentIntegrationContractInterface;

/**
 * Self-contained Accessing integration contract provider.
 *
 * Administering dry-runtime must not require the optional Accessing component
 * classes during container scan. The real Accessing component may provide a
 * richer bridge in a host application, but the base Administering package owns
 * this minimal contract shape.
 *
 * Tag: administering.component_integration_contract
 */
final class AdministrationAccessingComponentIntegrationContractProvider implements AdministrationComponentIntegrationContractInterface
{
    public function componentKey(): string
    {
        return 'accessing';
    }

    /**
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
