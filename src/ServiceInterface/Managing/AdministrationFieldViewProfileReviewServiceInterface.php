<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Managing;

use App\Managing\Value\Administration\ManagingFieldViewProfileEditRequest;
use App\Managing\Value\Administration\ManagingFieldViewProfileReviewResult;

interface AdministrationFieldViewProfileReviewServiceInterface
{
    public function review(ManagingFieldViewProfileEditRequest $request): ManagingFieldViewProfileReviewResult;
}
