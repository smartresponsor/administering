<?php

declare(strict_types=1);

namespace App\Administering\Service\Config;

use App\Administering\Entity\Config\AdministrationConfigApplication;
use App\Configuring\Value\Config\ConfigToolDescriptor;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

final readonly class AdministrationConfigToolRegistryService
{
    public function __construct(
        private AdministrationConfigApplicationDiscoveryService $applicationDiscoveryService,
        private AdministrationConfigToolServiceLocator $toolServiceLocator,
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

            $toolDescriptorsByApplication = [];
            foreach ($this->toolServiceLocator->descriptorsByApplicationCode() as $applicationCode => $toolDescriptors) {
                $toolDescriptorsByApplication[$applicationCode] = $toolDescriptors;
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
                foreach ($toolDescriptorsByApplication[$applicationDescriptor->applicationCode] ?? [] as $toolDescriptor) {
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

    /** @return list<ConfigToolDescriptor> */
    public function toolDescriptors(): array
    {
        return $this->toolServiceLocator->descriptors();
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
