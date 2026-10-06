<?php

declare(strict_types=1);

namespace App\Administering\Service\Managing;

use App\Administering\ServiceInterface\Managing\AdministrationFieldViewProfileReviewServiceInterface;
use App\Administering\Value\Managing\AdministrationManagingFieldViewProfileEditRequest;
use App\Administering\Value\Managing\AdministrationManagingFieldViewProfileReviewResult;

/**
 * Builds safe review payloads for Managing field view profile edits without requiring Managing runtime.
 */
final readonly class AdministrationManagingFieldViewProfileReviewService implements AdministrationFieldViewProfileReviewServiceInterface
{
    public function review(AdministrationManagingFieldViewProfileEditRequest $request): AdministrationManagingFieldViewProfileReviewResult
    {
        $violations = [];
        if ('' === trim($request->profileKey)) {
            $violations[] = 'Missing profile key.';
        }

        $changeType = $request->currentProfilePayload === $request->requestedProfilePayload ? 'no_change' : 'profile_payload_update';

        return new AdministrationManagingFieldViewProfileReviewResult(
            $request->profileKey,
            $changeType,
            [] === $violations,
            $request->requestedProfilePayload,
            [
                'requested_by_subject' => $request->requestedBySubject,
                'reason' => $request->reason,
                'mode' => 'administering_self_contained_dry_runtime',
            ],
            [],
            $violations,
        );
    }
}
