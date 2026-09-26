<?php

declare(strict_types=1);

namespace App\Administering\ValidatorInterface\Admin;

use App\Administering\ServiceInterface\Tool\AdministrationConfigurationToolProviderInterface;
use App\Administering\Value\Admin\AdministrationOwnerConfigurationToolViolation;
use App\Administering\Value\Tool\AdministrationConfigurationToolDefinition;

interface AdministrationConfigurationToolDefinitionValidatorInterface
{
    /**
     * @return list<AdministrationOwnerConfigurationToolViolation>
     */
    public function validate(
        AdministrationConfigurationToolProviderInterface $provider,
        AdministrationConfigurationToolDefinition $definition,
    ): array;
}
