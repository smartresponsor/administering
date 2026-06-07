<?php

declare(strict_types=1);

namespace App\Administering\Service\Managing;

use App\Administering\ServiceInterface\Managing\AdministrationFieldViewProfileApplyServiceInterface;
use App\Administering\Value\Managing\ManagingFieldViewProfileApplyRequest;
use App\Administering\Value\Managing\ManagingFieldViewProfileApplyResult;

/**
 * Prepares a reviewed Managing field view profile payload without writing owner storage.
 */
final readonly class AdministrationManagingFieldViewProfileApplyService implements AdministrationFieldViewProfileApplyServiceInterface
{
    public function prepare(ManagingFieldViewProfileApplyRequest $request): ManagingFieldViewProfileApplyResult
    {
        $valid = [] !== $request->normalizedProfilePayload;

        return new ManagingFieldViewProfileApplyResult(
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
