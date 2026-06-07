<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Managing;

use App\Administering\Value\Managing\ManagingFieldViewProfileEditRequest;
use App\Administering\Value\Managing\ManagingFieldViewProfileReviewResult;

interface AdministrationFieldViewProfileReviewServiceInterface
{
    public function review(ManagingFieldViewProfileEditRequest $request): ManagingFieldViewProfileReviewResult;
}
