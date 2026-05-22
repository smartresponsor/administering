<?php

declare(strict_types=1);

namespace App\Administering\Service\Rolling;

use App\Administering\ServiceInterface\Rolling\AdministrationPermissionCatalogProviderInterface;
use App\Administering\Value\Rolling\AdministrationPermissionDescriptor;

/**
 * Minimal catalog provider used until the host wires Rolling's real catalog service.
 */
final class BootstrapAdministrationPermissionCatalogProvider implements AdministrationPermissionCatalogProviderInterface
{
    /** @return list<AdministrationPermissionDescriptor> */
    public function descriptors(): array
    {
        return [
            new AdministrationPermissionDescriptor('administration.dashboard.view', 'View Admin dashboard', 'administration', ['global']),

            new AdministrationPermissionDescriptor('administration.operation.view', 'View Administering operation runs and reports', 'administration_operations', ['global', 'component']),
            new AdministrationPermissionDescriptor('administration.configuration.scan', 'Queue Administering configuration scan operation', 'administration_operations', ['global', 'component']),
            new AdministrationPermissionDescriptor('administration.credential.presence_check', 'Queue credential presence check operation', 'administration_operations', ['environment', 'component'], true),
            new AdministrationPermissionDescriptor('administration.composer.validate', 'Queue Composer validation operation', 'administration_operations', ['component']),
            new AdministrationPermissionDescriptor('administration.rolling_acl.catalog_refresh', 'Queue Rolling ACL catalog refresh operation', 'administration_operations', ['global', 'component']),
            new AdministrationPermissionDescriptor('administration.accessing_account.action', 'Queue Accessing account action operation', 'administration_operations', ['global']),
            new AdministrationPermissionDescriptor('administration.symfony_secret.set', 'Set credential through Symfony Secrets', 'credentials', ['environment', 'component'], true),
            new AdministrationPermissionDescriptor('administration.symfony_secret.remove', 'Remove credential through Symfony Secrets', 'credentials', ['environment', 'component'], true),
            new AdministrationPermissionDescriptor('administration.generated_patch.build', 'Build generated patch artifact', 'administration_operations', ['component'], true),

            new AdministrationPermissionDescriptor('administration.accessing.account.view', 'View Accessing accounts through Administering', 'accessing_accounts', ['global']),
            new AdministrationPermissionDescriptor('administration.accessing.account_action.view', 'View Accessing account action surface', 'accessing_accounts', ['global']),
            new AdministrationPermissionDescriptor('administration.accessing.account_action.execute', 'Execute safe Accessing account action request', 'accessing_accounts', ['global'], true),
            new AdministrationPermissionDescriptor('administration.accessing.account_action.audit.view', 'View Accessing account action audit projections', 'accessing_accounts', ['global']),

            new AdministrationPermissionDescriptor('administration.rolling.permission_catalog.view', 'View Rolling permission catalog through Administering', 'rolling_acl', ['global', 'component']),
            new AdministrationPermissionDescriptor('administration.rolling.subject_access_report.view', 'View Rolling subject access report through Administering', 'rolling_acl', ['global', 'component']),
            new AdministrationPermissionDescriptor('administration.rolling.acl_mutation.review.view', 'View Rolling ACL mutation review surface', 'rolling_acl', ['global', 'component']),
            new AdministrationPermissionDescriptor('administration.rolling.acl_mutation.review', 'Create Rolling ACL mutation review records', 'rolling_acl', ['global', 'component'], true),
            new AdministrationPermissionDescriptor('administration.rolling.acl_mutation.apply', 'Apply approved Rolling ACL mutations', 'rolling_acl', ['global', 'component'], true),
            new AdministrationPermissionDescriptor('administration.rolling.acl_mutation.apply.view', 'View Rolling ACL mutation apply reports', 'rolling_acl', ['global', 'component']),
            new AdministrationPermissionDescriptor('administration.rolling.acl.execution_report.view', 'View Rolling ACL execution reports', 'rolling_acl', ['global', 'component']),

            new AdministrationPermissionDescriptor('administration.connected_component.overview.view', 'View connected component overview', 'connected_components', ['global', 'component']),
            new AdministrationPermissionDescriptor('administration.connected_component.health.view', 'View connected component health report', 'connected_components', ['global', 'component']),
            new AdministrationPermissionDescriptor('administration.connected_component.readiness.view', 'View connected component readiness report', 'connected_components', ['global', 'component']),
            new AdministrationPermissionDescriptor('administration.connected_component.capability_matrix.view', 'View connected component capability matrix', 'connected_components', ['global', 'component']),
            new AdministrationPermissionDescriptor('administration.connected_component.contract_matrix.view', 'View connected component contract matrix', 'connected_components', ['global', 'component']),
            new AdministrationPermissionDescriptor('administration.connected_component.diagnostics.view', 'View connected component diagnostics', 'connected_components', ['global', 'component']),
            new AdministrationPermissionDescriptor('administration.connected_component.execution_plan.view', 'View connected component execution plan', 'connected_components', ['global', 'component']),
            new AdministrationPermissionDescriptor('administration.connected_component.remediation.view', 'View connected component remediation plan', 'connected_components', ['global', 'component']),
            new AdministrationPermissionDescriptor('administration.connected_component.work_plan.view', 'View connected component work plan', 'connected_components', ['global', 'component']),

            new AdministrationPermissionDescriptor('administration.config.view', 'View configuration state', 'configuration', ['global', 'component']),
            new AdministrationPermissionDescriptor('rolling.role.view', 'View Rolling roles', 'rolling_acl', ['global', 'component']),
            new AdministrationPermissionDescriptor('accessing.account.view', 'View Accessing accounts', 'accessing_accounts', ['global']),
        ];
    }
}
