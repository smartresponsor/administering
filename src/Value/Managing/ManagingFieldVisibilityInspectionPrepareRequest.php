<?php

declare(strict_types=1);

namespace App\Administering\Value\Managing;

final readonly class ManagingFieldVisibilityInspectionPrepareRequest
{
    /**
     * @param list<string> $statusCandidates
     * @param list<string> $publicationFlagCandidates
     * @param list<string> $publicationDateCandidates
     */
    public function __construct(
        public string $resourceClass,
        public string $fieldName,
        public string $pageName,
        public ?string $subjectIdentifier,
        public array $statusCandidates,
        public array $publicationFlagCandidates,
        public array $publicationDateCandidates,
        public string $requestedBySubject,
        public ?string $reason = null,
    ) {
    }
}
