<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Managing;

use App\Administering\Value\Managing\ManagingFieldAccessMutationReviewInput;
use App\Administering\Value\Managing\ManagingFieldAccessMutationReviewResult;

interface AdministrationFieldAccessMutationReviewServiceInterface
{
    public function review(ManagingFieldAccessMutationReviewInput $input): ManagingFieldAccessMutationReviewResult;
}
