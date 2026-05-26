<?php

declare(strict_types=1);

namespace App\Administering\Service\Connected;

use App\Administering\Entity\AdministrationConnectedComponentRecord;
use App\Administering\ServiceInterface\Admin\AdministrationServiceSectionAnchorSyncServiceInterface;
use App\Administering\ServiceInterface\Admin\AdministrationServiceToolHandlerInterface;
use App\Administering\ServiceInterface\Connected\AdministrationConnectedComponentOverviewProviderInterface;
use App\Administering\ServiceTrait\Admin\AdministrationServiceSectionAnchorSyncToolHandlerTrait;
use App\Administering\Value\Admin\AdministrationServiceSectionAnchorSyncResult;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Synchronizes the Connected Components primary CRUD anchor from the overview provider.
 */
final readonly class AdministrationConnectedComponentRecordSyncService implements AdministrationServiceSectionAnchorSyncServiceInterface, AdministrationServiceToolHandlerInterface
{
    use AdministrationServiceSectionAnchorSyncToolHandlerTrait;

    public function __construct(
        private AdministrationConnectedComponentOverviewProviderInterface $overviewProvider,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function sectionKey(): string
    {
        return 'Connected';
    }

    public function synchronize(): AdministrationServiceSectionAnchorSyncResult
    {
        $this->replaceRecords();
        $count = 0;

        foreach ($this->overviewProvider->overview()->statuses() as $status) {
            $readiness = in_array($status->status(), ['available', 'reportable', 'auditable'], true) ? 'ready' : 'review';
            $this->entityManager->persist(new AdministrationConnectedComponentRecord(
                componentName: $status->component(),
                status: $status->status(),
                readinessStatus: $readiness,
                safeSummary: [
                    'message' => $status->message(),
                    'metadata' => $status->metadata(),
                ],
            ));
            ++$count;
        }

        $this->entityManager->flush();

        return new AdministrationServiceSectionAnchorSyncResult($this->sectionKey(), $count);
    }

    private function replaceRecords(): void
    {
        $this->entityManager->createQueryBuilder()
            ->delete(AdministrationConnectedComponentRecord::class, 'record')
            ->getQuery()
            ->execute();
    }
}
