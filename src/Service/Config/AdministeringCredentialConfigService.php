<?php

declare(strict_types=1);

namespace App\Administering\Service\Config;

use App\Administering\Form\Config\AdministeringCredentialConfigFormType;
use App\Administering\ServiceInterface\Config\AdministrationConfigToolServiceInterface;
use App\Administering\Value\Config\AdministrationConfigToolDescriptor;
use App\Administering\Value\Form\Config\AdministeringCredentialConfigData;

final readonly class AdministeringCredentialConfigService implements AdministrationConfigToolServiceInterface
{
    public function __construct(
        private ConfigApplyService $applyService,
        private ConfigSecretService $secretService,
    ) {
    }

    public function descriptor(): AdministrationConfigToolDescriptor
    {
        return new AdministrationConfigToolDescriptor(
            applicationCode: 'Administering',
            toolCode: 'administering.credentials',
            label: 'Administering Credentials',
            description: 'Symfony Secrets replacement flow for approved host credentials.',
            formClass: AdministeringCredentialConfigFormType::class,
            serviceClass: self::class,
            requiredPermission: 'administration.symfony_secret.set',
            editableFields: ['appSecretReplacement', 'administrationDatabaseUrlReplacement'],
            sensitiveFields: ['appSecretReplacement', 'administrationDatabaseUrlReplacement'],
            readableFiles: [],
            writableFiles: [],
            metadata: ['environment' => 'prod'],
            secretNames: [
                'appSecretReplacement' => 'APP_SECRET',
                'administrationDatabaseUrlReplacement' => 'ADMINISTERING_DATABASE_URL',
            ],
            applyStrategy: 'symfony_secrets',
        );
    }

    public function loadData(): object
    {
        return new AdministeringCredentialConfigData();
    }

    public function save(object $data, array $context = []): array
    {
        $payload = $this->assertData($data);
        $values = $this->stateRows($payload, 'pending');

        return $this->applyService->save($this->descriptor(), (string) ($context['actor'] ?? 'system'), $values, [], [
            'app_secret_replacement' => '' !== $payload->appSecretReplacement ? '********' : null,
            'administration_database_url_replacement' => '' !== $payload->administrationDatabaseUrlReplacement ? '********' : null,
        ]);
    }

    public function apply(object $data, array $context = []): array
    {
        $payload = $this->assertData($data);
        $replacementValues = [
            'appSecretReplacement' => $payload->appSecretReplacement,
            'administrationDatabaseUrlReplacement' => $payload->administrationDatabaseUrlReplacement,
        ];

        $secretResult = $this->secretService->replace('prod', $replacementValues, $this->descriptor()->secretNames);
        $status = 'applied' === $secretResult['status'] ? 'applied' : 'failed';
        $values = $this->stateRows($payload, $status);

        return $this->applyService->apply(
            $this->descriptor(),
            (string) ($context['actor'] ?? 'system'),
            $values,
            [],
            $secretResult['masked_changes'],
            [],
            [[
                'status' => $secretResult['status'],
                'messages' => $secretResult['messages'],
                'masked_changes' => $secretResult['masked_changes'],
            ]],
            'applied' === $secretResult['status'] ? null : 'Secret replacement failed.',
            $status,
        );
    }

    private function assertData(object $data): AdministeringCredentialConfigData
    {
        if (!$data instanceof AdministeringCredentialConfigData) {
            throw new \InvalidArgumentException('Administering credential config expects AdministeringCredentialConfigData.');
        }

        return $data;
    }

    /**
     * @return array<string, array{fieldType:string, secret:bool, current:?string, pending:?string, masked:?string, status:string}>
     */
    private function stateRows(AdministeringCredentialConfigData $data, string $status): array
    {
        return [
            'app_secret_replacement' => ['fieldType' => 'password', 'secret' => true, 'current' => null, 'pending' => null, 'masked' => '' !== $data->appSecretReplacement ? '********' : null, 'status' => $status],
            'administration_database_url_replacement' => ['fieldType' => 'password', 'secret' => true, 'current' => null, 'pending' => null, 'masked' => '' !== $data->administrationDatabaseUrlReplacement ? '********' : null, 'status' => $status],
        ];
    }
}
