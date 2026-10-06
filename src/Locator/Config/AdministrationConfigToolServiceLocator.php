<?php

declare(strict_types=1);

namespace App\Administering\Locator\Config;

use App\Administering\Form\Config\AdministrationDynamicConfigToolFormType;
use App\Administering\ServiceInterface\Config\AdministrationConfigToolServiceInterface;
use App\Administering\ServiceInterface\Config\AdministrationManagedConfigVariablesProviderInterface;
use App\Administering\Value\Config\AdministrationConfigToolDescriptor;
use App\Administering\Value\Config\AdministrationConfigVariableStorage;

final readonly class AdministrationConfigToolServiceLocator
{
    /** @var list<AdministrationConfigToolServiceInterface> */
    private array $toolServices;

    /**
     * @param iterable<AdministrationConfigToolServiceInterface> $toolServices
     */
    public function __construct(iterable $toolServices = [])
    {
        $this->toolServices = $this->materializeToolServices($toolServices);
    }

    public function forTool(string $applicationCode, string $toolCode): ?AdministrationConfigToolServiceInterface
    {
        foreach ($this->toolServices as $service) {
            $descriptor = $service->descriptor();
            if ($descriptor->applicationCode === $applicationCode && $descriptor->toolCode === $toolCode) {
                return $service;
            }
        }

        return null;
    }

    /** @return list<AdministrationConfigToolDescriptor> */
    public function descriptors(): array
    {
        return array_map(
            fn (AdministrationConfigToolServiceInterface $service): AdministrationConfigToolDescriptor => $this->descriptorForService($service),
            $this->toolServices,
        );
    }

    /** @return list<AdministrationConfigToolDescriptor> */
    public function descriptorsForApplication(string $applicationCode): array
    {
        return array_values(array_filter(
            $this->descriptors(),
            static fn (AdministrationConfigToolDescriptor $descriptor): bool => $descriptor->applicationCode === $applicationCode,
        ));
    }

    /**
     * @param iterable<AdministrationConfigToolServiceInterface> $toolServices
     *
     * @return list<AdministrationConfigToolServiceInterface>
     */
    private function materializeToolServices(iterable $toolServices): array
    {
        $services = [];
        foreach ($toolServices as $toolService) {
            $services[] = $toolService;
        }

        return $services;
    }

    private function descriptorForService(AdministrationConfigToolServiceInterface $service): AdministrationConfigToolDescriptor
    {
        $descriptor = $service->descriptor();
        if (!$service instanceof AdministrationManagedConfigVariablesProviderInterface) {
            return $descriptor;
        }

        $variables = iterator_to_array($service->variables(), false);

        if ([] === $variables) {
            return $descriptor;
        }

        $editableFields = [];
        $sensitiveFields = [];
        $targetFiles = [];
        $secretNames = [];
        $managedVariableMetadata = [];

        foreach ($variables as $variable) {
            $editableFields[] = $variable->key;
            $managedVariableMetadata[] = $variable->toArray();

            if (AdministrationConfigVariableStorage::SECRET === $variable->storage) {
                $sensitiveFields[] = $variable->key;
                $secretNames[$variable->key] = $variable->key;
            }

            if (null !== $variable->targetFile && '' !== trim($variable->targetFile)) {
                $targetFiles[] = $variable->targetFile;
            }
        }

        $targetFiles = array_values(array_unique($targetFiles));

        return new AdministrationConfigToolDescriptor(
            applicationCode: $descriptor->applicationCode,
            toolCode: $descriptor->toolCode,
            label: $descriptor->label,
            description: $descriptor->description,
            formClass: AdministrationDynamicConfigToolFormType::class,
            serviceClass: $descriptor->serviceClass,
            requiredPermission: $descriptor->requiredPermission,
            editableFields: array_values(array_unique($editableFields)),
            sensitiveFields: array_values(array_unique($sensitiveFields)),
            readableFiles: $targetFiles,
            writableFiles: $targetFiles,
            metadata: array_replace($descriptor->metadata, [
                'source' => 'configuring.config_tool_service',
                'form_source' => 'configuring.managed_variables',
                'managed_variables' => $managedVariableMetadata,
            ]),
            secretNames: [] !== $secretNames ? $secretNames : $descriptor->secretNames,
            applyStrategy: $descriptor->applyStrategy,
        );
    }

    /** @return array<string, list<AdministrationConfigToolDescriptor>> */
    public function descriptorsByApplicationCode(): array
    {
        $descriptors = [];
        foreach ($this->toolServices as $service) {
            $descriptor = $this->descriptorForService($service);
            $descriptors[$descriptor->applicationCode][] = $descriptor;
        }

        ksort($descriptors);

        return $descriptors;
    }
}
