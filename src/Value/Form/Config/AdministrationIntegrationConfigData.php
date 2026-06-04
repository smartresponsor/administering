<?php

declare(strict_types=1);

namespace App\Administering\Value\Form\Config;

final class AdministrationIntegrationConfigData
{
    public string $uiLabel = 'Admin';
    public string $routePrefix = '/admin';
    public string $rollingExternalAccessBackend = 'rolling';
    public string $rollingExternalAccessFailureEffect = 'deny';
    public string $rollingExternalAccessPermissionKey = 'managing.field.view';
    public string $rollingExternalAccessReadinessSurface = 'administration_managing_rolling_field_access_readiness';
    public string $profileStorageEntityManager = 'system';
}
