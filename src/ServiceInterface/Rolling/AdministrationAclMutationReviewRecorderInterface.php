<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Rolling;

use App\Administering\Entity\AdministrationAclMutationReviewRecordEntity;
use App\Administering\Value\Rolling\AdministrationRollingAclMutationRequest;
use App\Administering\Value\Rolling\AdministrationRollingAclMutationReview;

interface AdministrationAclMutationReviewRecorderInterface
{
    public function record(AdministrationRollingAclMutationRequest $request, AdministrationRollingAclMutationReview $review): AdministrationAclMutationReviewRecordEntity;
}
