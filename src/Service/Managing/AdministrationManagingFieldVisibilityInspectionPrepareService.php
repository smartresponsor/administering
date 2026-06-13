<?php

declare(strict_types=1);

namespace App\Administering\Service\Managing;

use App\Administering\ServiceInterface\Managing\AdministrationFieldVisibilityInspectionPrepareServiceInterface;
use App\Administering\Value\Managing\ManagingFieldVisibilityInspectionPrepareRequest;
use App\Administering\Value\Managing\ManagingFieldVisibilityInspectionPrepareResult;

/**
 * Prepares read-only Managing field visibility inspection payloads without Managing runtime calls.
 */
final readonly class AdministrationManagingFieldVisibilityInspectionPrepareService implements AdministrationFieldVisibilityInspectionPrepareServiceInterface
{
    public function prepare(ManagingFieldVisibilityInspectionPrepareRequest $request): ManagingFieldVisibilityInspectionPrepareResult
    {
        $violations = [];
        foreach (['resource class' => $request->resourceClass, 'field nameEntity' => $request->fieldName, 'page nameEntity' => $request->pageName] as $label => $value) {
            if ('' === trim($value)) {
                $violations[] = sprintf('Missing %s.', $label);
            }
        }

        return new ManagingFieldVisibilityInspectionPrepareResult(
            [] === $violations,
            [] === $violations ? 'prepared' : 'rejected',
            [] === $violations ? 'Managing visibility inspection payload prepared for owner runtime.' : implode(' ', $violations),
            [
                'resource_class' => $request->resourceClass,
                'field_name' => $request->fieldName,
                'page_name' => $request->pageName,
                'subject_identifier' => $request->subjectIdentifier,
                'status_candidates' => $request->statusCandidates,
                'publication_flag_candidates' => $request->publicationFlagCandidates,
                'publication_date_candidates' => $request->publicationDateCandidates,
            ],
            [
                'requested_by_subject' => $request->requestedBySubject,
                'reason' => $request->reason,
                'mode' => 'administering_self_contained_dry_runtime',
            ],
        );
    }
}
