<?php

declare(strict_types=1);

namespace App\Administering\Value\Managing;

final readonly class ManagingFieldAccessMutationReviewInput
{
    public function __construct(
        public ManagingFieldAccessPolicyDescriptor $descriptor,
        public string $requestedBySubject,
    ) {
    }

    /** @return array<string, mixed> */
    public function toSafeContext(): array
    {
        return [
            'descriptor' => $this->descriptor->toSafeArray(),
            'requested_by_subject' => $this->requestedBySubject,
            'source' => 'administering_managing_field_access_review',
        ];
    }
}
