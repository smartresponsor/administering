<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Rolling;

use App\Administering\Value\Rolling\AdministrationAclMutationApplyResult;

interface AdministrationAclMutationApplyServiceInterface
{
    public function applyReviewedMutation(string $requestKey, string $requestedBySubject): AdministrationAclMutationApplyResult;
}
