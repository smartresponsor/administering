<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Rolling;

use App\Administering\Value\Rolling\AdministrationFieldAccessMutationReviewInput;
use App\Administering\Value\Rolling\AdministrationFieldAccessMutationReviewResult;

interface AdministrationFieldAccessMutationReviewServiceInterface
{
    public function review(AdministrationFieldAccessMutationReviewInput $input): AdministrationFieldAccessMutationReviewResult;
}
