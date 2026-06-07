<?php

declare(strict_types=1);

namespace App\Administering\Value\Managing;

use App\Administering\Value\Rolling\AdministrationRollingAclMutationReview;

final readonly class ManagingFieldAccessMutationReviewResult
{
    public function __construct(
        public ManagingFieldAccessPolicyDescriptor $descriptor,
        public AdministrationRollingAclMutationReview $review,
        public string $requestKey,
    ) {
    }

    /** @return array<string, mixed> */
    public function toSafeArray(): array
    {
        return [
            'descriptor' => $this->descriptor->toSafeArray(),
            'review' => $this->review->toSafeArray(),
            'request_key' => $this->requestKey,
        ];
    }
}
