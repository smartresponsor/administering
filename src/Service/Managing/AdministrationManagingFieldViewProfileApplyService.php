<?php

declare(strict_types=1);

namespace App\Administering\Service\Managing;

use App\Administering\ServiceInterface\Managing\AdministrationFieldViewProfileApplyServiceInterface;
use App\Administering\Value\Managing\AdministrationManagingFieldViewProfileApplyRequest;
use App\Administering\Value\Managing\AdministrationManagingFieldViewProfileApplyResult;

/**
 * Prepares a reviewed Managing field view profile payload without writing owner storage.
 */
final readonly class AdministrationManagingFieldViewProfileApplyService implements AdministrationFieldViewProfileApplyServiceInterface
{
    public function prepare(AdministrationManagingFieldViewProfileApplyRequest $request): AdministrationManagingFieldViewProfileApplyResult
    {
        $valid = [] !== $request->normalizedProfilePayload;

        return new AdministrationManagingFieldViewProfileApplyResult(
            $valid,
            $valid ? 'prepared' : 'rejected',
            $valid ? 'Managing profile apply payload prepared for owner runtime.' : 'Normalized profile payload is empty.',
            [
                'normalized_profile_payload' => $request->normalizedProfilePayload,
                'review_context' => $request->reviewContext,
            ],
            [
                'requested_by_subject' => $request->requestedBySubject,
                'reason' => $request->reason,
                'mode' => 'administering_self_contained_dry_runtime',
            ],
        );
    }
}
