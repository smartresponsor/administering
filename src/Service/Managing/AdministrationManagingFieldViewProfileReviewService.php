<?php

declare(strict_types=1);

namespace App\Administering\Service\Managing;

use App\Administering\ServiceInterface\Managing\AdministrationFieldViewProfileReviewServiceInterface;
use App\Managing\ServiceInterface\Administration\ManagingFieldViewProfileReviewServiceInterface as OwnerReviewServiceInterface;
use App\Managing\Value\Administration\ManagingFieldViewProfileEditRequest;
use App\Managing\Value\Administration\ManagingFieldViewProfileReviewResult;

/**
 * Builds safe review payloads for Managing field view profile edits.
 *
 * The service intentionally does not persist or apply the profile. It only normalizes the payload that a later
 * controlled workflow can store in system configuration/storage.
 */
final readonly class AdministrationManagingFieldViewProfileReviewService implements AdministrationFieldViewProfileReviewServiceInterface
{
    public function __construct(
        private OwnerReviewServiceInterface $reviewService,
    ) {
    }

    public function review(ManagingFieldViewProfileEditRequest $request): ManagingFieldViewProfileReviewResult
    {
        return $this->reviewService->review($request);
    }
}
