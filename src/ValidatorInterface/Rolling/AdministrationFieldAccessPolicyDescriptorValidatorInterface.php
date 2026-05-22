<?php

declare(strict_types=1);

namespace App\Administering\ValidatorInterface\Rolling;

use App\Administering\Value\Rolling\AdministrationFieldAccessPolicyDescriptor;

interface AdministrationFieldAccessPolicyDescriptorValidatorInterface
{
    public function assertValid(AdministrationFieldAccessPolicyDescriptor $descriptor): void;
}
