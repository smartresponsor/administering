<?php

declare(strict_types=1);

namespace App\Administering\Service\Managing;

use App\Administering\ServiceInterface\Managing\AdministrationFieldViewProfileApplyServiceInterface;
use App\Managing\ServiceInterface\Administration\ManagingFieldViewProfileApplyServiceInterface as OwnerApplyServiceInterface;
use App\Managing\Value\Administration\ManagingFieldViewProfileApplyRequest;
use App\Managing\Value\Administration\ManagingFieldViewProfileApplyResult;

/**
 * Prepares a reviewed Managing field view profile payload for the Managing apply handler.
 *
 * This service deliberately does not write to Managing storage. It validates the review context
 * and emits a payload that a host integration can submit to the Managing apply handler.
 */
final readonly class AdministrationManagingFieldViewProfileApplyService implements AdministrationFieldViewProfileApplyServiceInterface
{
    public function __construct(
        private OwnerApplyServiceInterface $applyService,
    ) {
    }

    public function prepare(ManagingFieldViewProfileApplyRequest $request): ManagingFieldViewProfileApplyResult
    {
        return $this->applyService->prepare($request);
    }
}
