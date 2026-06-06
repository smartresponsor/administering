<?php

declare(strict_types=1);

namespace App\Administering\Provider\Connected;

use App\Administering\Service\Connected\AdministrationRuntimeScopeConnectedComponentProjectionService;
use App\Administering\ServiceInterface\Connected\AdministrationConnectedComponentCapabilityMatrixProviderInterface;
use App\Administering\Value\Connected\AdministrationConnectedComponentCapability;
use App\Administering\Value\Connected\AdministrationConnectedComponentCapabilityMatrix;

/** Builds metadata-only capabilities without foreign service contracts. */
final readonly class AdministrationConnectedComponentCapabilityMatrixProvider implements AdministrationConnectedComponentCapabilityMatrixProviderInterface
{
    public function __construct(private AdministrationRuntimeScopeConnectedComponentProjectionService $projection)
    {
    }

    public function matrix(): AdministrationConnectedComponentCapabilityMatrix
    {
        $capabilities = [];
        foreach ($this->projection->decisionRows() as $row) {
            $component = $row->component;
            $capabilities[] = new AdministrationConnectedComponentCapability(
                component: $component,
                key: $component.'.runtime_scope.read',
                label: $component.' runtime-scope visibility',
                category: 'runtime_scope',
                status: $row->status,
                sensitive: false,
                mutation: false,
                requiresReview: false,
                context: $row->toArray(),
            );
        }

        return new AdministrationConnectedComponentCapabilityMatrix(new \DateTimeImmutable(), $capabilities, $this->guards());
    }

    /** @return list<string> */
    private function guards(): array
    {
        return [
            'No foreign PHP service contracts are used for capability discovery.',
            'Capabilities are derived only from APP_RUNTIME_SCOPE, composer inventory, and runtime-scope lock files.',
        ];
    }
}
