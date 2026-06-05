<?php

declare(strict_types=1);

namespace App\Administering\ValidatorInterface\Admin;

use App\Administering\Value\Admin\AdministrationOwnerConfigurationToolViolation;
use App\Configuring\ServiceInterface\Tool\ConfigurationToolProviderInterface;
use App\Configuring\Value\Tool\ConfigurationToolDefinition;

interface AdministrationConfigurationToolDefinitionValidatorInterface
{
    /**
     * @return list<AdministrationOwnerConfigurationToolViolation>
     */
    public function validate(
        ConfigurationToolProviderInterface $provider,
        ConfigurationToolDefinition $definition,
    ): array;
}
