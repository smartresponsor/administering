<?php

declare(strict_types=1);

namespace App\Administering\Service\Config;

use App\Administering\Entity\Config\AdministrationConfigApplyLogEntity;
use App\Administering\Repository\AdministrationPersistenceRepository;

final readonly class AdministrationConfigAuditService
{
    public function __construct(private AdministrationPersistenceRepository $persistenceRepository)
    {
    }

    /**
     * @param array<string, mixed> $changedFields
     * @param array<string, mixed> $maskedSecrets
     */
    public function record(
        string $applicationCode,
        string $toolCode,
        string $actorIdentifier,
        string $status,
        array $changedFields = [],
        array $maskedSecrets = [],
        ?string $errorMessage = null,
    ): AdministrationConfigApplyLogEntity {
        $log = new AdministrationConfigApplyLogEntity($applicationCode, $toolCode, $actorIdentifier, $status, $changedFields, $maskedSecrets, $errorMessage);
        $this->persistenceRepository->persist($log);

        return $log;
    }
}
