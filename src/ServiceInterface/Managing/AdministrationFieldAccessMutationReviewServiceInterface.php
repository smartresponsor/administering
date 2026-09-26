<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Managing;

use App\Administering\Value\Managing\AdministrationManagingFieldAccessMutationReviewInput;
use App\Administering\Value\Managing\AdministrationManagingFieldAccessMutationReviewResult;

interface AdministrationFieldAccessMutationReviewServiceInterface
{
    public function review(AdministrationManagingFieldAccessMutationReviewInput $input): AdministrationManagingFieldAccessMutationReviewResult;
}
