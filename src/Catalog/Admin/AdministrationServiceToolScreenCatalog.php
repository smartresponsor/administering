<?php

declare(strict_types=1);

namespace App\Administering\Catalog\Admin;

use App\Administering\CatalogInterface\Admin\AdministrationServiceToolScreenCatalogInterface;
use App\Administering\Value\Admin\AdministrationServiceToolScreen;

/**
 * Canonical service-tool to primary-screen mapping.
 *
 * FilesystemAdministrationServiceToolCatalog discovers tools from service files;
 * this catalog owns the optional screen mapping. Keeping these responsibilities
 * separate prevents the service filesystem catalog from becoming a route sitemap.
 */
final class AdministrationServiceToolScreenCatalog implements AdministrationServiceToolScreenCatalogInterface
{
    public function screenForTool(string $section, string $toolShortName): ?AdministrationServiceToolScreen
    {
        return $this->screensForSection($section)[$toolShortName] ?? null;
    }

    public function screensForSection(string $section): array
    {
        return self::screens()[$section] ?? [];
    }

    /**
     * @return array<string, array<string, AdministrationServiceToolScreen>>
     */
    private static function screens(): array
    {
        return [
            'Accessing' => [
                'AccessingAdministrationAccountActionAuditProjectionProvider' => new AdministrationServiceToolScreen('administration_accessing_account_action_audit', 'Open action audit'),
                'AccessingAdministrationAccountProjectionProvider' => new AdministrationServiceToolScreen('administration_accessing_accounts', 'Open accounts'),
            ],
            'Connected' => [
                'AdministrationConnectedComponentCapabilityMatrixProvider' => new AdministrationServiceToolScreen('administration_connected_component_capability_matrix', 'Open capability matrix'),
                'AdministrationConnectedComponentContractMatrixProvider' => new AdministrationServiceToolScreen('administration_connected_component_contract_matrix', 'Open contract matrix'),
                'AdministrationConnectedComponentDiagnosticReportProvider' => new AdministrationServiceToolScreen('administration_connected_component_diagnostics', 'Open diagnostics'),
                'AdministrationConnectedComponentExecutionPlanProvider' => new AdministrationServiceToolScreen('administration_connected_component_execution_plan', 'Open execution plan'),
                'AdministrationConnectedComponentHealthReportProvider' => new AdministrationServiceToolScreen('administration_connected_component_health', 'Open health report'),
                'AdministrationConnectedComponentOverviewProvider' => new AdministrationServiceToolScreen('administration_connected_component_overview', 'Open overview'),
                'AdministrationConnectedComponentReadinessReportProvider' => new AdministrationServiceToolScreen('administration_connected_component_readiness', 'Open readiness'),
                'AdministrationConnectedComponentRemediationPlanProvider' => new AdministrationServiceToolScreen('administration_connected_component_remediation', 'Open remediation'),
                'AdministrationConnectedComponentWorkPlanProvider' => new AdministrationServiceToolScreen('administration_connected_component_work_plan', 'Open work plan'),
            ],
            'Managing' => [
                'AdministrationManagingFieldAccessCatalogProvider' => new AdministrationServiceToolScreen('administration_managing_field_access_catalog', 'Open access catalog'),
                'AdministrationManagingFieldAccessMutationApplyService' => new AdministrationServiceToolScreen('administration_managing_field_access_mutation_apply', 'Open access apply'),
                'AdministrationManagingFieldAccessMutationReviewService' => new AdministrationServiceToolScreen('administration_managing_field_access_mutations', 'Open access mutations'),
                'AdministrationManagingFieldViewProfileApplyService' => new AdministrationServiceToolScreen('administration_managing_field_view_profile_apply', 'Open profile apply'),
                'AdministrationManagingFieldViewProfileCatalogProvider' => new AdministrationServiceToolScreen('administration_managing_field_view_profiles', 'Open view profiles'),
                'AdministrationManagingFieldViewProfileReviewService' => new AdministrationServiceToolScreen('administration_managing_field_view_profile_edit', 'Open profile review'),
                'AdministrationManagingFieldVisibilityExplanationCatalogProvider' => new AdministrationServiceToolScreen('administration_managing_field_visibility_explanation', 'Open explanation'),
                'AdministrationManagingFieldVisibilityInspectionPrepareService' => new AdministrationServiceToolScreen('administration_managing_field_visibility_inspection', 'Open inspection'),
                'AdministrationRollingBackedManagingAccessReadinessProvider' => new AdministrationServiceToolScreen('administration_managing_rolling_field_access_readiness', 'Open readiness'),
            ],
            'Operation' => [
                'AdministrationGovernanceOperationRunner' => new AdministrationServiceToolScreen('administration_operations', 'Open operations'),
                'DoctrineAdministrationOperationReportProvider' => new AdministrationServiceToolScreen('administering_operation_report_json', 'Open operation report API'),
            ],
            'Rolling' => [
                'DoctrineAdministrationAclMutationApplyReportProvider' => new AdministrationServiceToolScreen('administration_rolling_acl_mutation_apply_report', 'Open apply report'),
                'RollingAclManifestAdministrationPermissionCatalogProvider' => new AdministrationServiceToolScreen('administration_rolling_permission_catalog', 'Open permission catalog'),
                'RollingAdministrationAclMutationApplyService' => new AdministrationServiceToolScreen('administration_rolling_acl_mutation_apply', 'Open ACL apply'),
                'RollingAdministrationAclMutationExecutionReportProvider' => new AdministrationServiceToolScreen('administration_rolling_acl_mutation_execution_report', 'Open execution report'),
            ],
            'Symfony' => [
                'AdministrationSymfonyRouteCatalogProvider' => new AdministrationServiceToolScreen('administration_symfony_routes', 'Open route catalog'),
            ],
        ];
    }
}
