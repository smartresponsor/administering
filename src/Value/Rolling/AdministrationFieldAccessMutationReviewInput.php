<?php

declare(strict_types=1);

namespace App\Administering\Value\Rolling;

/**
 * Safe input shape for Administering-driven Managing field access mutation reviews.
 *
 * This DTO is review-only. It does not execute Rolling ACL mutations and carries no secrets.
 */
final readonly class AdministrationFieldAccessMutationReviewInput
{
    public function __construct(
        public AdministrationFieldAccessPolicyDescriptor $descriptor,
        public string $requestedBySubject,
    ) {
    }

    /** @return array<string, mixed> */
    public function toSafeContext(): array
    {
        return [
            'source' => 'administering_ui',
            'surface' => 'managing_field_access_mutation_review',
            'subject_type' => $this->descriptor->subjectType,
            'effect' => $this->descriptor->effect,
            'reason' => $this->descriptor->reason,
            'target' => $this->descriptor->target->toAuditContext(),
        ];
    }
}
