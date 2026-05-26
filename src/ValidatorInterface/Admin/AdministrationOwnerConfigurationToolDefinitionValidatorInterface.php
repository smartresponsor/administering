<?php

declare(strict_types=1);

namespace App\Administering\ValidatorInterface\Admin;

use App\Administering\ServiceInterface\Admin\AdministrationOwnerConfigurationToolProviderInterface;
use App\Administering\Value\Admin\AdministrationOwnerConfigurationToolDefinition;
use App\Administering\Value\Admin\AdministrationOwnerConfigurationToolViolation;

interface AdministrationOwnerConfigurationToolDefinitionValidatorInterface
{
    /**
     * @return list<AdministrationOwnerConfigurationToolViolation>
     */
    public function validate(
        AdministrationOwnerConfigurationToolProviderInterface $provider,
        AdministrationOwnerConfigurationToolDefinition $definition,
    ): array;
}
