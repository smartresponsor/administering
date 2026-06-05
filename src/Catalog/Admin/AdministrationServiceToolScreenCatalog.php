<?php

declare(strict_types=1);

namespace App\Administering\Catalog\Admin;

use App\Administering\CatalogInterface\Admin\AdministrationServiceToolScreenCatalogInterface;
use App\Administering\Value\Admin\AdministrationServiceToolScreen;

/**
 * Canonical service-tool to primary-screen mapping.
 *
 * AdministrationFilesystemServiceToolCatalog discovers tools from service files;
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
                'AdministrationManagingFieldAccessMutationApplyService' => new AdministrationServiceToolScreen('administration_managing_field_access_mutation_apply', 'Open access apply'),
                'AdministrationManagingFieldAccessMutationReviewService' => new AdministrationServiceToolScreen('administration_managing_field_access_mutations', 'Open access mutations'),
                'AdministrationManagingFieldViewProfileApplyService' => new AdministrationServiceToolScreen('administration_managing_field_view_profile_apply', 'Open profile apply'),
                'AdministrationManagingFieldViewProfileReviewService' => new AdministrationServiceToolScreen('administration_managing_field_view_profile_edit', 'Open profile review'),
                'AdministrationManagingFieldVisibilityInspectionPrepareService' => new AdministrationServiceToolScreen('administration_managing_field_visibility_inspection', 'Open inspection'),
            ],
            'Operation' => [
            ],
            'Rolling' => [
                'AdministrationRollingAclMutationApplyService' => new AdministrationServiceToolScreen('administration_rolling_acl_mutation_apply', 'Open ACL apply'),
            ],
            'Symfony' => [
            ],
        ];
    }
}
