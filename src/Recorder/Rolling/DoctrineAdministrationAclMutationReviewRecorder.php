<?php

declare(strict_types=1);

namespace App\Administering\Recorder\Rolling;

use App\Administering\Entity\AdministrationAclMutationReviewRecord;
use App\Administering\ServiceInterface\Audit\AdministrationAuditRecorderInterface;
use App\Administering\ServiceInterface\Rolling\AdministrationAclMutationReviewRecorderInterface;
use App\Rolling\Value\Administration\RollingAclMutationRequest;
use App\Rolling\Value\Administration\RollingAclMutationReview;
use Doctrine\Persistence\ManagerRegistry;

final readonly class DoctrineAdministrationAclMutationReviewRecorder implements AdministrationAclMutationReviewRecorderInterface
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private AdministrationAuditRecorderInterface $auditRecorder,
    ) {
    }

    public function record(RollingAclMutationRequest $request, RollingAclMutationReview $review): AdministrationAclMutationReviewRecord
    {
        $record = new AdministrationAclMutationReviewRecord(
            sprintf('acl-review-%s', bin2hex(random_bytes(8))),
            $review->mutationType(),
            $review->subjectIdentifier(),
            $review->permissionOrRoleKey(),
            $review->scopeKey(),
            $request->requestedBySubject(),
            $review->valid(),
            $review->toSafeArray(),
        );

        $manager = $this->manager();
        $manager->persist($record);
        $manager->flush();

        $this->auditRecorder->record('administration.rolling.acl_mutation.reviewed', $request->requestedBySubject(), [
            'request_key' => $record->requestKey(),
            'mutation_type' => $record->mutationType(),
            'subject_identifier' => $record->subjectIdentifier(),
            'permission_or_role_key' => $record->permissionOrRoleKey(),
            'scope_key' => $record->scopeKey(),
            'valid' => $record->valid(),
        ]);

        return $record;
    }

    private function manager(): \Doctrine\Persistence\ObjectManager
    {
        $manager = $this->managerRegistry->getManagerForClass(AdministrationAclMutationReviewRecord::class);

        if (null === $manager) {
            throw new \LogicException('No Doctrine manager is configured for Administering ACL mutation review records. Configure the system SQLite entity manager for App\\Administering entities.');
        }

        return $manager;
    }
}
