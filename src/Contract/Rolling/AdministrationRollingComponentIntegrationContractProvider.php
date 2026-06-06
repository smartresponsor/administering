<?php

declare(strict_types=1);

namespace App\Administering\Contract\Rolling;

use App\Administering\Contract\AdministrationComponentIntegrationContractInterface;

/**
 * Self-contained Rolling integration contract provider.
 *
 * Administering dry-runtime must not require the optional Rolling component
 * classes during container scan. The real Rolling component may provide a
 * richer bridge in a host application, but the base Administering package owns
 * this minimal contract shape.
 *
 * Tag: administering.component_integration_contract
 */
final class AdministrationRollingComponentIntegrationContractProvider implements AdministrationComponentIntegrationContractInterface
{
    public function componentKey(): string
    {
        return 'rolling';
    }

    /**
     * @return object{owns: string, subjectPrefix: string, permissionPrefix: string}
     */
    public function contract(): object
    {
        return new readonly class('permission_decision', 'rolling:subject:', 'administration.') {
            public function __construct(
                public string $owns,
                public string $subjectPrefix,
                public string $permissionPrefix,
            ) {
            }
        };
    }
}
