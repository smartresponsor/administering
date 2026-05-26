<?php

declare(strict_types=1);

namespace App\Administering\Service\Config;

use App\Administering\Entity\Config\AdministrationConfigApplyLog;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

final readonly class ConfigAuditService
{
    public function __construct(private ManagerRegistry $managerRegistry)
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
    ): AdministrationConfigApplyLog {
        $manager = $this->entityManager();
        $log = new AdministrationConfigApplyLog($applicationCode, $toolCode, $actorIdentifier, $status, $changedFields, $maskedSecrets, $errorMessage);
        $manager->persist($log);
        $manager->flush();

        return $log;
    }

    private function entityManager(): EntityManagerInterface
    {
        $manager = $this->managerRegistry->getManagerForClass(AdministrationConfigApplyLog::class);
        if (!$manager instanceof EntityManagerInterface) {
            throw new \LogicException('No Doctrine entity manager is configured for Administering config apply logs.');
        }

        return $manager;
    }
}
