<?php

declare(strict_types=1);

namespace App\Administering\Service\Connected;

use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationContractMatrixProviderInterface;
use App\Administering\ServiceInterface\Connected\AdministrationConnectedComponentContractMatrixProviderInterface;
use App\Administering\Value\Connected\AdministrationConnectedComponentContract;
use App\Administering\Value\Connected\AdministrationConnectedComponentContractMatrix;
use App\Rolling\ServiceInterface\Administration\RollingAclAdministrationContractMatrixProviderInterface;

/**
 * Aggregates safe contract matrices from connected components.
 */
final readonly class AdministrationConnectedComponentContractMatrixProvider implements AdministrationConnectedComponentContractMatrixProviderInterface
{
    public function __construct(
        private AccessingAccountAdministrationContractMatrixProviderInterface $accessingContractMatrixProvider,
        private RollingAclAdministrationContractMatrixProviderInterface $rollingContractMatrixProvider,
    ) {
    }

    public function matrix(): AdministrationConnectedComponentContractMatrix
    {
        $contracts = [];

        foreach ($this->accessingContractMatrixProvider->matrix()->contracts() as $contract) {
            $contracts[] = $this->mapContract('Accessing', $contract->toSafeArray());
        }

        foreach ($this->rollingContractMatrixProvider->matrix()->contracts() as $contract) {
            $contracts[] = $this->mapContract('Rolling', $contract->toSafeArray());
        }

        return new AdministrationConnectedComponentContractMatrix(
            new \DateTimeImmutable(),
            $contracts,
            [
                'This endpoint is a contract matrix, not an executor.',
                'Accessing contracts must not duplicate authentication/session ownership in Administering.',
                'Rolling contracts must not expose raw subject grants or policy internals.',
                'Administering consumes safe contracts and routes reviewed requests to owning components only.',
            ],
        );
    }

    /** @param array<string, mixed> $contract */
    private function mapContract(string $component, array $contract): AdministrationConnectedComponentContract
    {
        $runtimeMode = (string) ($contract['runtimeMode'] ?? $contract['storageMode'] ?? 'unknown');

        return new AdministrationConnectedComponentContract(
            $component,
            (string) $contract['key'],
            (string) $contract['label'],
            (string) $contract['category'],
            (string) $contract['status'],
            (string) ($contract['provider'] ?? $component),
            (string) ($contract['consumer'] ?? 'Administering'),
            (bool) ($contract['required'] ?? true),
            (bool) ($contract['sensitive'] ?? false),
            $runtimeMode,
            is_array($contract['context'] ?? null) ? $contract['context'] : [],
        );
    }
}
