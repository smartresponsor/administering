<?php

declare(strict_types=1);

namespace App\Administering\Provider\Operation;

use App\Administering\Entity\AdministrationOperationArtifact;
use App\Administering\Entity\AdministrationOperationEvent;
use App\Administering\Entity\AdministrationOperationRun;
use App\Administering\ServiceInterface\Operation\AdministrationOperationReportProviderInterface;
use App\Administering\Value\Operation\AdministrationOperationReport;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Builds a metadata-only operation report from system SQLite records.
 */
final class DoctrineAdministrationOperationReportProvider implements AdministrationOperationReportProviderInterface
{
    public function __construct(private readonly ManagerRegistry $managerRegistry)
    {
    }

    public function reportFor(string $operationKey): AdministrationOperationReport
    {
        $manager = $this->manager();

        $run = $manager
            ->getRepository(AdministrationOperationRun::class)
            ->findOneBy(['operationKey' => $operationKey]);

        $events = $manager
            ->getRepository(AdministrationOperationEvent::class)
            ->findBy(['operationKey' => $operationKey], ['id' => 'ASC']);

        $artifacts = $manager
            ->getRepository(AdministrationOperationArtifact::class)
            ->findBy(['operationKey' => $operationKey], ['id' => 'ASC']);

        return new AdministrationOperationReport(
            $operationKey,
            $run instanceof AdministrationOperationRun ? $run->getStatus() : 'unknown',
            $run instanceof AdministrationOperationRun ? $run->getOperationType() : 'Unknown operation',
            array_map(static fn (AdministrationOperationEvent $event): array => [
                'status' => $event->getStatus(),
                'safe_message' => $event->getSafeMessage(),
                'safe_context' => $event->getSafeContext(),
                'created_at' => $event->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ], $events),
            array_map(static fn (AdministrationOperationArtifact $artifact): array => [
                'artifact_type' => $artifact->getArtifactType(),
                'safe_label' => $artifact->getSafeLabel(),
                'relative_path' => $artifact->getRelativePath(),
                'checksum' => $artifact->getChecksum(),
                'safe_context' => $artifact->getSafeContext(),
                'created_at' => $artifact->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ], $artifacts),
            [
                'events' => count($events),
                'artifacts' => count($artifacts),
            ],
        );
    }

    private function manager(): \Doctrine\Persistence\ObjectManager
    {
        $manager = $this->managerRegistry->getManagerForClass(AdministrationOperationRun::class);

        if (null === $manager) {
            throw new \LogicException('No Doctrine manager is configured for Administering operation reports. Configure the system SQLite entity manager for App\\Administering entities.');
        }

        return $manager;
    }
}
