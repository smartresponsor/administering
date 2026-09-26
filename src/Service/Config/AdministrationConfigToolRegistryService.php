<?php

declare(strict_types=1);

namespace App\Administering\Service\Config;

use App\Administering\Locator\Config\AdministrationConfigToolServiceLocator;
use App\Administering\Repository\Config\AdministrationConfigRegistryRepository;
use App\Administering\Value\Config\AdministrationConfigToolDescriptor;

final readonly class AdministrationConfigToolRegistryService
{
    public function __construct(
        private AdministrationConfigApplicationDiscoveryService $applicationDiscoveryService,
        private AdministrationConfigToolServiceLocator $toolServiceLocator,
        private AdministrationConfigRegistryRepository $configRegistryRepository,
    ) {
    }

    /**
     * @return array{applications:int, tools:int}
     */
    public function sync(): array
    {
        $applicationDescriptors = [];
        foreach ($this->applicationDiscoveryService->discover() as $applicationDescriptor) {
            $applicationDescriptors[$applicationDescriptor->applicationCode] = $applicationDescriptor;
        }

        $toolDescriptorsByApplication = [];
        foreach ($this->toolServiceLocator->descriptorsByApplicationCode() as $applicationCode => $toolDescriptors) {
            $toolDescriptorsByApplication[$applicationCode] = $toolDescriptors;
        }

        return $this->configRegistryRepository->replace($applicationDescriptors, $toolDescriptorsByApplication);
    }

    /** @return list<AdministrationConfigToolDescriptor> */
    public function toolDescriptors(): array
    {
        return $this->toolServiceLocator->descriptors();
    }
}
