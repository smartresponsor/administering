<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Managing;

use App\Administering\Value\Managing\ManagingFieldViewProfileApplyRequest;
use App\Administering\Value\Managing\ManagingFieldViewProfileApplyResult;

interface AdministrationFieldViewProfileApplyServiceInterface
{
    public function prepare(ManagingFieldViewProfileApplyRequest $request): ManagingFieldViewProfileApplyResult;
}
