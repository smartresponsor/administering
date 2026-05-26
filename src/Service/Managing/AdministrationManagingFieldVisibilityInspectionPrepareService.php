<?php

declare(strict_types=1);

namespace App\Administering\Service\Managing;

use App\Administering\ServiceInterface\Managing\AdministrationFieldVisibilityInspectionPrepareServiceInterface;
use App\Managing\ServiceInterface\Administration\ManagingFieldVisibilityInspectionPrepareServiceInterface as OwnerPrepareServiceInterface;
use App\Managing\Value\Administration\ManagingFieldVisibilityInspectionPrepareRequest;
use App\Managing\Value\Administration\ManagingFieldVisibilityInspectionPrepareResult;

/**
 * Prepares a concrete Managing field visibility inspection request from Administering.
 *
 * This service does not call Managing runtime directly. It emits a normalized payload that a host
 * integration or operator can submit to the Managing inspector/console command.
 */
final readonly class AdministrationManagingFieldVisibilityInspectionPrepareService implements AdministrationFieldVisibilityInspectionPrepareServiceInterface
{
    public function __construct(
        private OwnerPrepareServiceInterface $prepareService,
    ) {
    }

    public function prepare(ManagingFieldVisibilityInspectionPrepareRequest $request): ManagingFieldVisibilityInspectionPrepareResult
    {
        return $this->prepareService->prepare($request);
    }
}
