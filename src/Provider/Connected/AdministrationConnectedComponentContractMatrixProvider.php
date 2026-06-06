<?php

declare(strict_types=1);

namespace App\Administering\Provider\Connected;

use App\Administering\Service\Connected\AdministrationRuntimeScopeConnectedComponentProjectionService;
use App\Administering\ServiceInterface\Connected\AdministrationConnectedComponentContractMatrixProviderInterface;
use App\Administering\Value\Connected\AdministrationConnectedComponentContract;
use App\Administering\Value\Connected\AdministrationConnectedComponentContractMatrix;

/** Builds metadata-only contract rows without importing foreign contracts. */
final readonly class AdministrationConnectedComponentContractMatrixProvider implements AdministrationConnectedComponentContractMatrixProviderInterface
{
    public function __construct(private AdministrationRuntimeScopeConnectedComponentProjectionService $projection)
    {
    }

    public function matrix(): AdministrationConnectedComponentContractMatrix
    {
        $contracts = [];
        foreach ($this->projection->decisionRows() as $row) {
            $component = $row->component;
            $contracts[] = new AdministrationConnectedComponentContract(
                component: $component,
                key: $component.'.runtime_scope.evidence',
                label: $component.' runtime evidence contract',
                category: 'runtime_scope',
                status: $row->status,
                provider: 'composer/runtime_scope_lock',
                consumer: 'administering',
                required: 'administering' === $component,
                sensitive: false,
                runtimeMode: 'evidence_only',
                context: $row->toArray(),
            );
        }

        return new AdministrationConnectedComponentContractMatrix(new \DateTimeImmutable(), $contracts, $this->guards());
    }

    /** @return list<string> */
    private function guards(): array
    {
        return [
            'Foreign component contracts are treated as strings/evidence, not PHP types.',
            'Administering remains standalone when APP_RUNTIME_SCOPE is empty.',
        ];
    }
}
