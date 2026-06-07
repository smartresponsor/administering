<?php

declare(strict_types=1);

namespace App\Administering\Value\Managing;

final readonly class ManagingFieldViewProfileReviewResult
{
    /**
     * @param array<string, mixed> $normalizedProfilePayload
     * @param array<string, mixed> $reviewContext
     * @param list<string>         $warnings
     * @param list<string>         $violations
     */
    public function __construct(
        public string $profileKey,
        public string $changeType,
        public bool $valid,
        public array $normalizedProfilePayload,
        public array $reviewContext,
        public array $warnings = [],
        public array $violations = [],
    ) {
    }

    /** @return array<string, mixed> */
    public function toSafeArray(): array
    {
        return [
            'profile_key' => $this->profileKey,
            'change_type' => $this->changeType,
            'valid' => $this->valid,
            'normalized_profile_payload' => $this->normalizedProfilePayload,
            'review_context' => $this->reviewContext,
            'warnings' => $this->warnings,
            'violations' => $this->violations,
            'safety' => [
                'grants_access' => false,
                'security_authority' => 'rolling',
            ],
        ];
    }
}
