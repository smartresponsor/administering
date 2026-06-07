<?php

declare(strict_types=1);

namespace App\Administering\Value\Managing;

final readonly class ManagingFieldViewProfileEditRequest
{
    /**
     * @param array<string, mixed> $currentProfilePayload
     * @param array<string, mixed> $requestedProfilePayload
     */
    public function __construct(
        public string $profileKey,
        public array $currentProfilePayload,
        public array $requestedProfilePayload,
        public string $requestedBySubject,
        public ?string $reason = null,
    ) {
    }
}
