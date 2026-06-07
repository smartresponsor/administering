<?php

declare(strict_types=1);

namespace App\Administering\Provider\Rolling;

use App\Administering\Entity\AdministrationAclMutationApplyRecord;
use App\Administering\ServiceInterface\Rolling\AdministrationAclMutationApplyReportProviderInterface;
use App\Administering\Value\Managing\ManagingAclMutationApplySummary;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine-backed metadata-only report provider for Rolling ACL apply attempts.
 */
final readonly class DoctrineAdministrationAclMutationApplyReportProvider implements AdministrationAclMutationApplyReportProviderInterface
{
    public function __construct(private ManagerRegistry $managerRegistry)
    {
    }

    /** @return list<AdministrationAclMutationApplyRecord> */
    public function recent(int $limit = 50): array
    {
        $safeLimit = max(1, min(200, $limit));

        return $this->manager()
            ->getRepository(AdministrationAclMutationApplyRecord::class)
            ->findBy([], ['id' => 'DESC'], $safeLimit);
    }

    public function summary(int $limit = 200): ManagingAclMutationApplySummary
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

        return new ManagingAclMutationApplySummary(
            count($records),
            $succeeded,
            $failed,
            $countByStatus,
            $countByMutationType,
            $latestAt,
        );
    }

    private function manager(): \Doctrine\Persistence\ObjectManager
    {
        $manager = $this->managerRegistry->getManagerForClass(AdministrationAclMutationApplyRecord::class);

        if (null === $manager) {
            throw new \LogicException('No Doctrine manager is configured for Administering ACL mutation apply records. Configure the system SQLite entity manager for App\\Administering entities.');
        }

        return $manager;
    }
}
