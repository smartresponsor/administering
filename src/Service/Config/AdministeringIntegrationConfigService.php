<?php

declare(strict_types=1);

namespace App\Administering\Service\Config;

use App\Administering\Form\Config\AdministeringIntegrationConfigFormType;
use App\Administering\ServiceInterface\Config\AdministrationConfigToolServiceInterface;
use App\Administering\Value\Config\AdministrationConfigToolDescriptor;
use App\Administering\Value\Form\Config\AdministeringIntegrationConfigData;
use Symfony\Component\Yaml\Yaml;

final readonly class AdministeringIntegrationConfigService implements AdministrationConfigToolServiceInterface
{
    public function __construct(
        private string $projectDir,
        private ConfigApplyService $applyService,
        private ConfigFileWriterService $fileWriter,
    ) {
    }

    public function descriptor(): AdministrationConfigToolDescriptor
    {
        return new AdministrationConfigToolDescriptor(
            applicationCode: 'Administering',
            toolCode: 'administering.integration',
            label: 'Administering Integration',
            description: 'Safe integration flags and component metadata stored in the component manifest.',
            formClass: AdministeringIntegrationConfigFormType::class,
            serviceClass: self::class,
            requiredPermission: 'administration.config.update',
            editableFields: [
                'uiLabel',
                'routePrefix',
                'rollingExternalAccessBackend',
                'rollingExternalAccessFailureEffect',
                'rollingExternalAccessPermissionKey',
                'rollingExternalAccessReadinessSurface',
                'profileStorageEntityManager',
            ],
            sensitiveFields: [],
            readableFiles: ['config/component/component.yaml'],
            writableFiles: ['config/component/component.yaml'],
            metadata: ['section' => 'Configuration'],
            secretNames: [],
            applyStrategy: 'component_yaml',
        );
    }

    public function loadData(): object
    {
        $data = new AdministeringIntegrationConfigData();
        $manifest = $this->manifest();

        $data->uiLabel = (string) ($manifest['ui_label'] ?? $data->uiLabel);
        $data->routePrefix = (string) ($manifest['route_prefix'] ?? $data->routePrefix);
        $data->rollingExternalAccessBackend = (string) ($manifest['integrations']['rolling']['managing_rolling_external_access_backend'] ?? $data->rollingExternalAccessBackend);
        $data->rollingExternalAccessFailureEffect = (string) ($manifest['integrations']['rolling']['managing_rolling_external_access_failure_effect'] ?? $data->rollingExternalAccessFailureEffect);
        $data->rollingExternalAccessPermissionKey = (string) ($manifest['integrations']['rolling']['managing_rolling_external_access_permission_key'] ?? $data->rollingExternalAccessPermissionKey);
        $data->rollingExternalAccessReadinessSurface = (string) ($manifest['integrations']['rolling']['managing_rolling_external_access_readiness_surface'] ?? $data->rollingExternalAccessReadinessSurface);
        $data->profileStorageEntityManager = (string) ($manifest['integrations']['rolling']['managing_profile_storage_entity_manager'] ?? $data->profileStorageEntityManager);

        return $data;
    }

    public function save(object $data, array $context = []): array
    {
        $payload = $this->assertData($data);
        $values = $this->stateRows($payload, 'pending');
        $masked = [
            'ui_label' => $payload->uiLabel,
            'route_prefix' => $payload->routePrefix,
            'rolling_external_access_backend' => $payload->rollingExternalAccessBackend,
            'rolling_external_access_failure_effect' => $payload->rollingExternalAccessFailureEffect,
            'rolling_external_access_permission_key' => $payload->rollingExternalAccessPermissionKey,
            'rolling_external_access_readiness_surface' => $payload->rollingExternalAccessReadinessSurface,
            'profile_storage_entity_manager' => $payload->profileStorageEntityManager,
        ];

        return $this->applyService->save($this->descriptor(), (string) ($context['actor'] ?? 'system'), $values, $masked, []);
    }

    public function apply(object $data, array $context = []): array
    {
        $payload = $this->assertData($data);
        $patch = $this->manifestPatch($payload);
        $write = $this->fileWriter->write(
            $this->projectDir.'/../Administering',
            'config/component/component.yaml',
            $patch,
            $this->descriptor()->writableFiles,
        );

        $status = 'applied' === $write['status'] ? 'applied' : 'failed';
        $values = $this->stateRows($payload, $status);

        return $this->applyService->apply(
            $this->descriptor(),
            (string) ($context['actor'] ?? 'system'),
            $values,
            $patch,
            [],
            [[
                'path' => $write['path'],
                'backup_path' => $write['backup_path'],
                'status' => $write['status'],
                'message' => $write['message'],
            ]],
            [],
            'applied' === $write['status'] ? null : $write['message'],
            $status,
        );
    }

    private function assertData(object $data): AdministeringIntegrationConfigData
    {
        if (!$data instanceof AdministeringIntegrationConfigData) {
            throw new \InvalidArgumentException('Administering integration config expects AdministeringIntegrationConfigData.');
        }

        return $data;
    }

    /** @return array<string, mixed> */
    private function manifest(): array
    {
        $path = $this->projectDir.'/../Administering/config/component/component.yaml';
        $parsed = is_file($path) ? Yaml::parseFile($path) : [];

        return is_array($parsed) ? $parsed : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function manifestPatch(AdministeringIntegrationConfigData $data): array
    {
        return [
            'ui_label' => $data->uiLabel,
            'route_prefix' => $data->routePrefix,
            'integrations' => [
                'rolling' => [
                    'managing_rolling_external_access_backend' => $data->rollingExternalAccessBackend,
                    'managing_rolling_external_access_failure_effect' => $data->rollingExternalAccessFailureEffect,
                    'managing_rolling_external_access_permission_key' => $data->rollingExternalAccessPermissionKey,
                    'managing_rolling_external_access_readiness_surface' => $data->rollingExternalAccessReadinessSurface,
                    'managing_profile_storage_entity_manager' => $data->profileStorageEntityManager,
                ],
            ],
        ];
    }

    /**
     * @return array<string, array{fieldType:string, secret:bool, current:?string, pending:?string, masked:?string, status:string}>
     */
    private function stateRows(AdministeringIntegrationConfigData $data, string $status): array
    {
        return [
            'ui_label' => ['fieldType' => 'string', 'secret' => false, 'current' => $data->uiLabel, 'pending' => $data->uiLabel, 'masked' => null, 'status' => $status],
            'route_prefix' => ['fieldType' => 'string', 'secret' => false, 'current' => $data->routePrefix, 'pending' => $data->routePrefix, 'masked' => null, 'status' => $status],
            'rolling_external_access_backend' => ['fieldType' => 'string', 'secret' => false, 'current' => $data->rollingExternalAccessBackend, 'pending' => $data->rollingExternalAccessBackend, 'masked' => null, 'status' => $status],
            'rolling_external_access_failure_effect' => ['fieldType' => 'string', 'secret' => false, 'current' => $data->rollingExternalAccessFailureEffect, 'pending' => $data->rollingExternalAccessFailureEffect, 'masked' => null, 'status' => $status],
            'rolling_external_access_permission_key' => ['fieldType' => 'string', 'secret' => false, 'current' => $data->rollingExternalAccessPermissionKey, 'pending' => $data->rollingExternalAccessPermissionKey, 'masked' => null, 'status' => $status],
            'rolling_external_access_readiness_surface' => ['fieldType' => 'string', 'secret' => false, 'current' => $data->rollingExternalAccessReadinessSurface, 'pending' => $data->rollingExternalAccessReadinessSurface, 'masked' => null, 'status' => $status],
            'profile_storage_entity_manager' => ['fieldType' => 'string', 'secret' => false, 'current' => $data->profileStorageEntityManager, 'pending' => $data->profileStorageEntityManager, 'masked' => null, 'status' => $status],
        ];
    }
}
