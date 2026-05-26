<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Rolling;

use App\Managing\Value\Administration\ManagingAclMutationApplyResult;

interface AdministrationAclMutationApplyServiceInterface
{
    public function applyReviewedMutation(string $requestKey, string $requestedBySubject): ManagingAclMutationApplyResult;
}
