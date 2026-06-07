<?php

declare(strict_types=1);

namespace App\Administering\Service\Config;

use App\Administering\Value\Config\ConfigToolDescriptor;

final readonly class AdministrationConfigApplyService
{
    public function __construct(
        private AdministrationConfigStateService $stateService,
        private AdministrationConfigAuditService $auditService,
    ) {
    }

    /**
     * @param array<string, array{fieldType:string, secret:bool, current:?string, pending:?string, masked:?string, status:string}> $values
     * @param array<string, mixed>                                                                                                 $changedFields
     * @param array<string, mixed>                                                                                                 $maskedSecrets
     *
     * @return array{status:string, messages:list<string>, masked_changes:array<string, mixed>, file_changes:list<array<string, mixed>>, secret_changes:list<array<string, mixed>>}
     */
    public function save(
        ConfigToolDescriptor $descriptor,
        string $actorIdentifier,
        array $values,
        array $changedFields = [],
        array $maskedSecrets = [],
    ): array {
        $this->stateService->replaceToolValues($descriptor->applicationCode, $descriptor->toolCode, $values);
        $this->auditService->record($descriptor->applicationCode, $descriptor->toolCode, $actorIdentifier, 'pending', $changedFields, $maskedSecrets);

        return [
            'status' => 'pending',
            'messages' => ['Pending configuration values have been stored in SQLite.'],
            'masked_changes' => $maskedSecrets,
            'file_changes' => [],
            'secret_changes' => [],
        ];
    }

    /**
     * @param array<string, array{fieldType:string, secret:bool, current:?string, pending:?string, masked:?string, status:string}> $values
     * @param array<string, mixed>                                                                                                 $changedFields
     * @param array<string, mixed>                                                                                                 $maskedSecrets
     * @param list<array<string, mixed>>                                                                                           $fileChanges
     * @param list<array<string, mixed>>                                                                                           $secretChanges
     *
     * @return array{status:string, messages:list<string>, masked_changes:array<string, mixed>, file_changes:list<array<string, mixed>>, secret_changes:list<array<string, mixed>>}
     */
    public function apply(
        ConfigToolDescriptor $descriptor,
        string $actorIdentifier,
        array $values,
        array $changedFields = [],
        array $maskedSecrets = [],
        array $fileChanges = [],
        array $secretChanges = [],
        ?string $errorMessage = null,
        string $status = 'applied',
    ): array {
        $this->stateService->replaceToolValues($descriptor->applicationCode, $descriptor->toolCode, $values);
        $this->auditService->record($descriptor->applicationCode, $descriptor->toolCode, $actorIdentifier, $status, $changedFields, $maskedSecrets, $errorMessage);

        return [
            'status' => $status,
            'messages' => $errorMessage ? [$errorMessage] : ['Configuration changes have been processed.'],
            'masked_changes' => $maskedSecrets,
            'file_changes' => $fileChanges,
            'secret_changes' => $secretChanges,
        ];
    }
}
