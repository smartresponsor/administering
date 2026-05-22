<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Rolling;

use App\Administering\Value\Rolling\AdministrationAclMutationApplyResult;

interface AdministrationFieldAccessMutationApplyServiceInterface
{
    public function applyReviewedFieldAccessMutation(string $requestKey, string $requestedBySubject): AdministrationAclMutationApplyResult;
}
