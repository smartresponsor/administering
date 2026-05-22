<?php

declare(strict_types=1);

namespace App\Administering\Value\Rolling;

/**
 * Apply-preparation request for a reviewed Managing field view profile payload.
 */
final readonly class AdministrationFieldViewProfileApplyRequest
{
    /**
     * @param array<string, mixed> $normalizedProfilePayload
     * @param array<string, mixed> $reviewContext
     */
    public function __construct(
        public array $normalizedProfilePayload,
        public array $reviewContext,
        public string $requestedBySubject = 'administering:anonymous',
        public ?string $reason = null,
    ) {
    }
}
