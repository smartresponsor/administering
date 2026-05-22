<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Rolling;

use App\Administering\Entity\AdministrationAclMutationReviewRecord;
use App\Rolling\Value\Administration\RollingAclMutationRequest;
use App\Rolling\Value\Administration\RollingAclMutationReview;

interface AdministrationAclMutationReviewRecorderInterface
{
    public function record(RollingAclMutationRequest $request, RollingAclMutationReview $review): AdministrationAclMutationReviewRecord;
}
