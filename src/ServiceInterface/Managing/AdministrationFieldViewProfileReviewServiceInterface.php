<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Managing;

use App\Administering\Value\Managing\AdministrationManagingFieldViewProfileEditRequest;
use App\Administering\Value\Managing\AdministrationManagingFieldViewProfileReviewResult;

interface AdministrationFieldViewProfileReviewServiceInterface
{
    public function review(AdministrationManagingFieldViewProfileEditRequest $request): AdministrationManagingFieldViewProfileReviewResult;
}
