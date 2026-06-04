<?php

declare(strict_types=1);

namespace App\Administering\Service\Config;

use App\Administering\Form\Config\DynamicConfigToolFormType;
use App\Configuring\ServiceInterface\Config\ConfigToolServiceInterface;
use App\Configuring\ServiceInterface\Config\ManagedConfigVariablesProviderInterface;
use App\Configuring\Value\Config\ConfigToolDescriptor;
use App\Configuring\Value\Config\ConfigVariable;
use App\Configuring\Value\Config\ConfigVariableStorage;

final readonly class AdministrationConfigToolServiceLocator
{
    /** @var list<ConfigToolServiceInterface> */
    private array $toolServices;

    /**
     * @param iterable<ConfigToolServiceInterface> $toolServices
     */
    public function __construct(iterable $toolServices = [])
    {
        $this->toolServices = $this->materializeToolServices($toolServices);
    }

    public function forTool(string $applicationCode, string $toolCode): ?ConfigToolServiceInterface
    {
        foreach ($this->toolServices as $service) {
            $descriptor = $service->descriptor();
            if ($descriptor->applicationCode === $applicationCode && $descriptor->toolCode === $toolCode) {
                return $service;
            }
        }

        return null;
    }

    /** @return list<ConfigToolDescriptor> */
    public function descriptors(): array
    {
        return array_values(array_map(
            fn (ConfigToolServiceInterface $service): ConfigToolDescriptor => $this->descriptorForService($service),
            $this->toolServices,
        ));
    }

    /** @return list<ConfigToolDescriptor> */
    public function descriptorsForApplication(string $applicationCode): array
    {
        return array_values(array_filter(
            $this->descriptors(),
            static fn (ConfigToolDescriptor $descriptor): bool => $descriptor->applicationCode === $applicationCode,
        ));
    }

    /** @param iterable<ConfigToolServiceInterface> $toolServices */
    private function materializeToolServices(iterable $toolServices): array
    {
        $services = [];
        foreach ($toolServices as $toolService) {
            if ($toolService instanceof ConfigToolServiceInterface) {
                $services[] = $toolService;
            }
        }

        return $services;
    }

    private function descriptorForService(ConfigToolServiceInterface $service): ConfigToolDescriptor
    {
        $descriptor = $service->descriptor();
        if (!$service instanceof ManagedConfigVariablesProviderInterface) {
            return $descriptor;
        }

        $variables = array_values(array_filter(
            iterator_to_array($service->managedVariables(), false),
            static fn (mixed $variable): bool => $variable instanceof ConfigVariable,
        ));

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

            if (ConfigVariableStorage::SECRET === $variable->storage) {
                $sensitiveFields[] = $variable->key;
                $secretNames[$variable->key] = $variable->key;
            }

            if (null !== $variable->targetFile && '' !== trim($variable->targetFile)) {
                $targetFiles[] = $variable->targetFile;
            }
        }

        $targetFiles = array_values(array_unique($targetFiles));

        return new ConfigToolDescriptor(
            applicationCode: $descriptor->applicationCode,
            toolCode: $descriptor->toolCode,
            label: $descriptor->label,
            description: $descriptor->description,
            formClass: DynamicConfigToolFormType::class,
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
}
