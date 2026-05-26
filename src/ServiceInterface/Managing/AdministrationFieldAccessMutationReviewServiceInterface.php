<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Managing;

use App\Managing\Value\Administration\ManagingFieldAccessMutationReviewInput;
use App\Managing\Value\Administration\ManagingFieldAccessMutationReviewResult;

interface AdministrationFieldAccessMutationReviewServiceInterface
{
    public function review(ManagingFieldAccessMutationReviewInput $input): ManagingFieldAccessMutationReviewResult;
}
