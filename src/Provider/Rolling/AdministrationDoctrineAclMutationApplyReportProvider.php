<?php

declare(strict_types=1);

namespace App\Administering\Provider\Rolling;

use App\Administering\Entity\AdministrationAclMutationApplyRecordEntity;
use App\Administering\Repository\AdministrationPersistenceRepository;
use App\Administering\ServiceInterface\Rolling\AdministrationAclMutationApplyReportProviderInterface;
use App\Administering\Value\Managing\AdministrationManagingAclMutationApplySummary;

/**
 * Doctrine-backed metadata-only report provider for Rolling ACL apply attempts.
 */
final readonly class AdministrationDoctrineAclMutationApplyReportProvider implements AdministrationAclMutationApplyReportProviderInterface
{
    public function __construct(private AdministrationPersistenceRepository $persistenceRepository)
    {
    }

    /** @return list<AdministrationAclMutationApplyRecordEntity> */
    public function recent(int $limit = 50): array
    {
        $safeLimit = max(1, min(200, $limit));

        return $this->persistenceRepository->findBy(AdministrationAclMutationApplyRecordEntity::class, [], ['id' => 'DESC'], $safeLimit);
    }

    public function summary(int $limit = 200): AdministrationManagingAclMutationApplySummary
    {
        $records = $this->recent($limit);
        $countByStatus = [];
        $countByMutationType = [];
        $succeeded = 0;
        $failed = 0;
        $latestAt = null;

        foreach ($records as $record) {
            $countByStatus[$record->status()] = ($countByStatus[$record->status()] ?? 0) + 1;
            $countByMutationType[$record->mutationType()] = ($countByMutationType[$record->mutationType()] ?? 0) + 1;

            if ($record->succeeded()) {
                ++$succeeded;
            } else {
                ++$failed;
            }

            if (null === $latestAt || $record->createdAt() > $latestAt) {
                $latestAt = $record->createdAt();
            }
        }

        ksort($countByStatus);
        ksort($countByMutationType);

        return new AdministrationManagingAclMutationApplySummary(
            count($records),
            $succeeded,
            $failed,
            $countByStatus,
            $countByMutationType,
            $latestAt,
        );
    }
}
