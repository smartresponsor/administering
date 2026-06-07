<?php

declare(strict_types=1);

namespace App\Administering\ValidatorInterface\Admin;

use App\Administering\ServiceInterface\Tool\ConfigurationToolProviderInterface;
use App\Administering\Value\Admin\AdministrationOwnerConfigurationToolViolation;
use App\Administering\Value\Tool\ConfigurationToolDefinition;

interface ConfigurationToolDefinitionValidatorInterface
{
    /**
     * @return list<AdministrationOwnerConfigurationToolViolation>
     */
    public function validate(
        ConfigurationToolProviderInterface $provider,
        ConfigurationToolDefinition $definition,
    ): array;
}
