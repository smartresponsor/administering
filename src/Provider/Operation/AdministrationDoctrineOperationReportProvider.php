<?php

declare(strict_types=1);

namespace App\Administering\Provider\Operation;

use App\Administering\Entity\AdministrationOperationArtifactEntity;
use App\Administering\Entity\AdministrationOperationEventEntity;
use App\Administering\Entity\AdministrationOperationRunEntity;
use App\Administering\Repository\AdministrationPersistenceRepository;
use App\Administering\ServiceInterface\Operation\AdministrationOperationReportProviderInterface;
use App\Administering\Value\Operation\AdministrationOperationReport;

/**
 * Builds a metadata-only operation report from system SQLite records.
 */
final class AdministrationDoctrineOperationReportProvider implements AdministrationOperationReportProviderInterface
{
    public function __construct(private readonly AdministrationPersistenceRepository $persistenceRepository)
    {
    }

    public function reportFor(string $operationKey): AdministrationOperationReport
    {
        $run = $this->persistenceRepository->findOneBy(AdministrationOperationRunEntity::class, ['operationKey' => $operationKey]);
        $events = $this->persistenceRepository->findBy(AdministrationOperationEventEntity::class, ['operationKey' => $operationKey], ['id' => 'ASC']);
        $artifacts = $this->persistenceRepository->findBy(AdministrationOperationArtifactEntity::class, ['operationKey' => $operationKey], ['id' => 'ASC']);

        return new AdministrationOperationReport(
            $operationKey,
            $run instanceof AdministrationOperationRunEntity ? $run->getStatus() : 'unknown',
            $run instanceof AdministrationOperationRunEntity ? $run->getOperationType() : 'Unknown operation',
            array_map(static fn (AdministrationOperationEventEntity $event): array => [
                'status' => $event->getStatus(),
                'safe_message' => $event->getSafeMessage(),
                'safe_context' => $event->getSafeContext(),
                'created_at' => $event->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ], $events),
            array_map(static fn (AdministrationOperationArtifactEntity $artifact): array => [
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
}
