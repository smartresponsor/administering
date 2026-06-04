<?php

declare(strict_types=1);

namespace App\Administering\Provider\Connected;

use App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationCapabilityMatrixProviderInterface;
use App\Administering\ServiceInterface\Connected\AdministrationConnectedComponentCapabilityMatrixProviderInterface;
use App\Administering\Value\Connected\AdministrationConnectedComponentCapability;
use App\Administering\Value\Connected\AdministrationConnectedComponentCapabilityMatrix;
use App\Rolling\ServiceInterface\Administration\RollingAclCapabilityMatrixProviderInterface;

/**
 * Aggregates safe capability matrices from connected components.
 */
final readonly class AdministrationConnectedComponentCapabilityMatrixProvider implements AdministrationConnectedComponentCapabilityMatrixProviderInterface
{
    public function __construct(
        private AccessAccountAdministrationCapabilityMatrixProviderInterface $accessingCapabilityMatrixProvider,
        private RollingAclCapabilityMatrixProviderInterface $rollingCapabilityMatrixProvider,
    ) {
    }

    public function matrix(): AdministrationConnectedComponentCapabilityMatrix
    {
        $capabilities = [];

        foreach ($this->accessingCapabilityMatrixProvider->matrix()->capabilities() as $capability) {
            $capabilities[] = $this->mapCapability('Accessing', $capability->toSafeArray(), 'executable');
        }

        foreach ($this->rollingCapabilityMatrixProvider->matrix()->capabilities() as $capability) {
            $capabilities[] = $this->mapCapability('Rolling', $capability->toSafeArray(), 'mutation');
        }

        return new AdministrationConnectedComponentCapabilityMatrix(
            new \DateTimeImmutable(),
            $capabilities,
            [
                'This endpoint is a capability matrix, not an executor.',
                'Accessing capabilities describe account-administration surfaces without exposing auth internals.',
                'Rolling capabilities describe ACL/policy surfaces without exposing raw grants or policy internals.',
                'Administering may visualize capabilities and route reviewed requests to owning components only.',
            ],
        );
    }

    /** @param array<string, mixed> $capability */
    private function mapCapability(string $component, array $capability, string $mutationFlag): AdministrationConnectedComponentCapability
    {
        return new AdministrationConnectedComponentCapability(
            $component,
            (string) $capability['key'],
            (string) $capability['label'],
            (string) $capability['category'],
            (string) $capability['status'],
            (bool) ($capability['sensitive'] ?? false),
            (bool) ($capability[$mutationFlag] ?? false),
            (bool) ($capability['requiresReview'] ?? true),
            is_array($capability['context'] ?? null) ? $capability['context'] : [],
        );
    }
}
