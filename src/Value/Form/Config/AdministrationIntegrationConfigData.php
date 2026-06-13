<?php

declare(strict_types=1);

namespace App\Administering\Value\Form\Config;

final class AdministrationIntegrationConfigData
{
    public string $uiLabel = 'Admin';
    public string $routePrefix = '/admin';
    public string $externalAccessBackend = 'authorization';
    public string $externalAccessFailureEffect = 'deny';
    public string $externalAccessPermissionKey = 'authorization.field.view';
    public string $externalAccessReadinessSurface = 'administration_connected_component_readiness';
    public string $rollingExternalAccessBackend = 'authorization';
    public string $rollingExternalAccessFailureEffect = 'deny';
    public string $rollingExternalAccessPermissionKey = 'authorization.field.view';
    public string $rollingExternalAccessReadinessSurface = 'administration_connected_component_readiness';
    public string $profileStorageEntityManager = 'system';
}
