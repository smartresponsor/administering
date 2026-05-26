<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Managing;

use App\Managing\Value\Administration\ManagingFieldViewProfileApplyRequest;
use App\Managing\Value\Administration\ManagingFieldViewProfileApplyResult;

interface AdministrationFieldViewProfileApplyServiceInterface
{
    public function prepare(ManagingFieldViewProfileApplyRequest $request): ManagingFieldViewProfileApplyResult;
}
