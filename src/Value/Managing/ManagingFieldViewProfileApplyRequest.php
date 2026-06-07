<?php

declare(strict_types=1);

namespace App\Administering\Value\Managing;

final readonly class ManagingFieldViewProfileApplyRequest
{
    /**
     * @param array<string, mixed> $normalizedProfilePayload
     * @param array<string, mixed> $reviewContext
     */
    public function __construct(
        public array $normalizedProfilePayload,
        public array $reviewContext,
        public string $requestedBySubject,
        public ?string $reason = null,
    ) {
    }
}
