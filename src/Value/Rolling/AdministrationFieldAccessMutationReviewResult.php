<?php

declare(strict_types=1);

namespace App\Administering\Value\Rolling;

use App\Administering\Entity\AdministrationAclMutationReviewRecord;
use App\Rolling\Value\Administration\RollingAclMutationReview;

/**
 * Review result returned to Administering field access control-plane screens.
 */
final readonly class AdministrationFieldAccessMutationReviewResult
{
    public function __construct(
        public AdministrationFieldAccessPolicyDescriptor $descriptor,
        public RollingAclMutationReview $review,
        public AdministrationAclMutationReviewRecord $record,
    ) {
    }

    /** @return array<string, mixed> */
    public function toSafeArray(): array
    {
        return [
            'record_key' => $this->record->requestKey(),
            'descriptor' => [
                'permission' => $this->descriptor->permissionKey,
                'subject_type' => $this->descriptor->subjectType,
                'subject_identifier' => $this->descriptor->subjectIdentifier,
                'effect' => $this->descriptor->effect,
                'target' => $this->descriptor->target->toAuditContext(),
            ],
            'review' => $this->review->toSafeArray(),
        ];
    }
}
