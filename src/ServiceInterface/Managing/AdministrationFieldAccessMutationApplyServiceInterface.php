<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Managing;

use App\Administering\Value\Managing\ManagingAclMutationApplyResult;

interface AdministrationFieldAccessMutationApplyServiceInterface
{
    public function applyReviewedFieldAccessMutation(string $requestKey, string $requestedBySubject): ManagingAclMutationApplyResult;
}
