<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Rolling;

use App\Administering\Value\Rolling\AdministrationFieldViewProfileEditRequest;
use App\Administering\Value\Rolling\AdministrationFieldViewProfileReviewResult;

interface AdministrationFieldViewProfileReviewServiceInterface
{
    public function review(AdministrationFieldViewProfileEditRequest $request): AdministrationFieldViewProfileReviewResult;
}
