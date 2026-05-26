<?php

declare(strict_types=1);

namespace App\Administering\Service\Config;

use App\Administering\Entity\Config\AdministrationConfigApplication;
use App\Administering\Value\Config\AdministrationConfigToolDescriptor;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Yaml\Yaml;

final readonly class ConfigToolRegistryService
{
    public function __construct(
        private ConfigApplicationDiscoveryService $applicationDiscoveryService,
        private ManagerRegistry $managerRegistry,
    ) {
    }

    /**
     * @return array{applications:int, tools:int}
     */
    public function sync(): array
    {
        $manager = $this->entityManager();
        $connection = $manager->getConnection();
        $connection->beginTransaction();

        try {
            $connection->executeStatement('DELETE FROM administration_config_tool');
            $connection->executeStatement('DELETE FROM administration_config_application');

            $applicationDescriptors = [];
            foreach ($this->applicationDiscoveryService->discover() as $applicationDescriptor) {
                $applicationDescriptors[$applicationDescriptor->applicationCode] = $applicationDescriptor;
            }

            $applications = 0;
            $tools = 0;

            foreach ($applicationDescriptors as $applicationDescriptor) {
                $connection->insert('administration_config_application', [
                    'application_code' => $applicationDescriptor->applicationCode,
                    'label' => $applicationDescriptor->label,
                    'root_path' => $applicationDescriptor->rootPath,
                    'manifest_path' => $applicationDescriptor->manifestPath,
                    'status' => 'discovered',
                    'enabled' => true,
                    'checksum' => $applicationDescriptor->checksum,
                    'discovered_at' => new \DateTimeImmutable(),
                ], [
                    'application_code' => ParameterType::STRING,
                    'label' => ParameterType::STRING,
                    'root_path' => ParameterType::STRING,
                    'manifest_path' => ParameterType::STRING,
                    'status' => ParameterType::STRING,
                    'enabled' => ParameterType::BOOLEAN,
                    'checksum' => ParameterType::STRING,
                    'discovered_at' => Types::DATETIME_IMMUTABLE,
                ]);
                ++$applications;

                $toolDescriptors = [];
                foreach ($this->toolDescriptorsForApplication($applicationDescriptor->rootPath, $applicationDescriptor->applicationCode) as $toolDescriptor) {
                    $toolDescriptors[$toolDescriptor->applicationCode.'::'.$toolDescriptor->toolCode] = $toolDescriptor;
                }

                foreach ($toolDescriptors as $toolDescriptor) {
                    $connection->insert('administration_config_tool', [
                        'application_code' => $toolDescriptor->applicationCode,
                        'tool_code' => $toolDescriptor->toolCode,
                        'label' => $toolDescriptor->label,
                        'description' => $toolDescriptor->description,
                        'form_class' => $toolDescriptor->formClass,
                        'service_class' => $toolDescriptor->serviceClass,
                        'required_permission' => $toolDescriptor->requiredPermission,
                        'apply_strategy' => $toolDescriptor->applyStrategy,
                        'status' => 'discovered',
                        'editable_fields' => $toolDescriptor->editableFields,
                        'sensitive_fields' => $toolDescriptor->sensitiveFields,
                        'readable_files' => $toolDescriptor->readableFiles,
                        'writable_files' => $toolDescriptor->writableFiles,
                        'metadata' => $toolDescriptor->metadata,
                        'secret_names' => $toolDescriptor->secretNames,
                        'discovered_at' => new \DateTimeImmutable(),
                    ], [
                        'application_code' => ParameterType::STRING,
                        'tool_code' => ParameterType::STRING,
                        'label' => ParameterType::STRING,
                        'description' => ParameterType::STRING,
                        'form_class' => ParameterType::STRING,
                        'service_class' => ParameterType::STRING,
                        'required_permission' => ParameterType::STRING,
                        'apply_strategy' => ParameterType::STRING,
                        'status' => ParameterType::STRING,
                        'editable_fields' => Types::JSON,
                        'sensitive_fields' => Types::JSON,
                        'readable_files' => Types::JSON,
                        'writable_files' => Types::JSON,
                        'metadata' => Types::JSON,
                        'secret_names' => Types::JSON,
                        'discovered_at' => Types::DATETIME_IMMUTABLE,
                    ]);
                    ++$tools;
                }
            }

            $connection->commit();

            return ['applications' => $applications, 'tools' => $tools];
        } catch (\Throwable $throwable) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            throw $throwable;
        }
    }

    /** @return list<AdministrationConfigToolDescriptor> */
    public function toolDescriptors(): array
    {
        $descriptors = [];
        foreach ($this->applicationDiscoveryService->discover() as $applicationDescriptor) {
            $descriptors = array_merge($descriptors, $this->toolDescriptorsForApplication($applicationDescriptor->rootPath, $applicationDescriptor->applicationCode));
        }

        return $descriptors;
    }

    /**
     * @return list<AdministrationConfigToolDescriptor>
     */
    private function toolDescriptorsForApplication(string $rootPath, string $applicationCode): array
    {
        $manifestPath = rtrim($rootPath, '/\\').'/config/component/config_tools.yaml';
        if (!is_file($manifestPath)) {
            return [];
        }

        $parsed = Yaml::parseFile($manifestPath);
        if (!is_array($parsed) || !is_array($parsed['config_tools'] ?? null)) {
            return [];
        }

        $tools = [];
        foreach ($parsed['config_tools'] as $tool) {
            if (!is_array($tool)) {
                continue;
            }

            $toolDescriptor = new AdministrationConfigToolDescriptor(
                applicationCode: (string) ($tool['application_code'] ?? $applicationCode),
                toolCode: (string) ($tool['tool_code'] ?? ''),
                label: (string) ($tool['label'] ?? ''),
                description: (string) ($tool['description'] ?? ''),
                formClass: (string) ($tool['form_class'] ?? ''),
                serviceClass: (string) ($tool['service_class'] ?? ''),
                requiredPermission: (string) ($tool['required_permission'] ?? 'administration.config.view'),
                editableFields: array_values(array_map('strval', is_array($tool['editable_fields'] ?? null) ? $tool['editable_fields'] : [])),
                sensitiveFields: array_values(array_map('strval', is_array($tool['sensitive_fields'] ?? null) ? $tool['sensitive_fields'] : [])),
                readableFiles: array_values(array_map('strval', is_array($tool['readable_files'] ?? null) ? $tool['readable_files'] : [])),
                writableFiles: array_values(array_map('strval', is_array($tool['writable_files'] ?? null) ? $tool['writable_files'] : [])),
                metadata: is_array($tool['metadata'] ?? null) ? $tool['metadata'] : [],
                secretNames: $this->secretNames($tool['secret_names'] ?? null),
                applyStrategy: (string) ($tool['apply_strategy'] ?? 'component_yaml'),
            );

            $toolKey = $toolDescriptor->applicationCode.'::'.$toolDescriptor->toolCode;
            $tools[$toolKey] = $toolDescriptor;
        }

        return array_values($tools);
    }

    /**
     * @return array<string, string>
     */
    private function secretNames(mixed $secretNames): array
    {
        if (!is_array($secretNames)) {
            return [];
        }

        $mapped = [];
        foreach ($secretNames as $fieldKey => $secretName) {
            if (!is_string($fieldKey) || !is_string($secretName)) {
                continue;
            }

            $mapped[$fieldKey] = $secretName;
        }

        return $mapped;
    }

    private function entityManager(): EntityManagerInterface
    {
        $manager = $this->managerRegistry->getManagerForClass(AdministrationConfigApplication::class);
        if (!$manager instanceof EntityManagerInterface) {
            throw new \LogicException('No Doctrine entity manager is configured for Administering config registry records.');
        }

        return $manager;
    }
}
