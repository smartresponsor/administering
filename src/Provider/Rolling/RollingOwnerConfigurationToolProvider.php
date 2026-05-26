<?php

declare(strict_types=1);

namespace App\Administering\Provider\Rolling;

use App\Administering\ServiceInterface\Admin\AdministrationOwnerConfigurationToolProviderInterface;
use App\Administering\Value\Admin\AdministrationOwnerConfigurationToolDefinition;
use App\Rolling\Form\Config\RollingConfigurationRoleRuntimeFormType;
use App\Rolling\Service\Config\RollingConfigurationRoleRuntimeService;
use App\Rolling\Value\Form\Config\RollingConfigurationRoleRuntimeData;

final readonly class RollingOwnerConfigurationToolProvider implements AdministrationOwnerConfigurationToolProviderInterface
{
    public function componentKey(): string
    {
        return 'Rolling';
    }

    public function componentToken(): string
    {
        return 'rolling';
    }

    /** @return iterable<AdministrationOwnerConfigurationToolDefinition> */
    public function tools(): iterable
    {
        yield new AdministrationOwnerConfigurationToolDefinition(
            componentKey: 'Rolling',
            componentToken: 'rolling',
            toolSlug: 'RoleRuntime',
            serviceClass: RollingConfigurationRoleRuntimeService::class,
            serviceShortName: 'RollingConfigurationRoleRuntimeService',
            label: 'Rolling Role Runtime',
            formTypeClass: RollingConfigurationRoleRuntimeFormType::class,
            formDataClass: RollingConfigurationRoleRuntimeData::class,
            executable: true,
            kind: 'owner_configuration_tool',
            operationType: 'service_tool_launch',
        );
    }
}
