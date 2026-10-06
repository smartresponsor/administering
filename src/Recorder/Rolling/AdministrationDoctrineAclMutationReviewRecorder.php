<?php

declare(strict_types=1);

namespace App\Administering\Recorder\Rolling;

use App\Administering\Entity\AdministrationAclMutationReviewRecordEntity;
use App\Administering\Repository\AdministrationPersistenceRepository;
use App\Administering\ServiceInterface\Audit\AdministrationAuditRecorderInterface;
use App\Administering\ServiceInterface\Rolling\AdministrationAclMutationReviewRecorderInterface;
use App\Administering\Value\Rolling\AdministrationRollingAclMutationRequest;
use App\Administering\Value\Rolling\AdministrationRollingAclMutationReview;

final readonly class AdministrationDoctrineAclMutationReviewRecorder implements AdministrationAclMutationReviewRecorderInterface
{
    public function __construct(
        private AdministrationPersistenceRepository $persistenceRepository,
        private AdministrationAuditRecorderInterface $auditRecorder,
    ) {
    }

    public function record(AdministrationRollingAclMutationRequest $request, AdministrationRollingAclMutationReview $review): AdministrationAclMutationReviewRecordEntity
    {
        $record = new AdministrationAclMutationReviewRecordEntity(
            sprintf('acl-review-%s', bin2hex(random_bytes(8))),
            $review->mutationType(),
            $review->subjectIdentifier(),
            $review->permissionOrRoleKey(),
            $review->scopeKey(),
            $request->requestedBySubject(),
            $review->valid(),
            $review->toSafeArray(),
        );

        $this->persistenceRepository->persist($record);

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
}
