<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin;

use App\Accessing\Entity\AccessAccountEntity;
use App\Administering\Controller\Admin\Crud\AdministrationAccountActionRequestRecordCrudController;
use App\Administering\Controller\Admin\Crud\AdministrationAclMutationApplyRecordCrudController;
use App\Administering\Controller\Admin\Crud\AdministrationAclMutationReviewRecordCrudController;
use App\Administering\Controller\Admin\Crud\AdministrationAuditEventCrudController;
use App\Administering\Controller\Admin\Crud\AdministrationChangeRequestCrudController;
use App\Administering\Controller\Admin\Crud\AdministrationConfigSnapshotCrudController;
use App\Administering\Controller\Admin\Crud\AdministrationCredentialDefinitionCrudController;
use App\Administering\Controller\Admin\Crud\AdministrationCredentialStateCrudController;
use App\Administering\Controller\Admin\Crud\AdministrationOperationArtifactCrudController;
use App\Administering\Controller\Admin\Crud\AdministrationOperationEventCrudController;
use App\Administering\Controller\Admin\Crud\AdministrationOperationRunCrudController;
use App\Administering\Controller\Admin\Crud\RollingAclRuleCrudController;
use App\Administering\Controller\Admin\Crud\RollingPermissionCrudController;
use App\Administering\Controller\Admin\Crud\RollingRoleCrudController;
use App\Administering\Controller\Admin\Crud\RollingRolePermissionCrudController;
use App\Administering\Controller\Admin\Crud\RollingSubjectRoleAssignmentCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'administration_admin_index')]
final class AdministrationDashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        if (!$this->getUser() instanceof AccessAccountEntity) {
            return $this->redirectToRoute('accessing_sign_in');
        }

        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return parent::index();
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('Administering');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home')
            ->setPermission('administration.dashboard.view');
        yield MenuItem::section('Configuration Governance');
        yield MenuItem::linkTo(AdministrationConfigSnapshotCrudController::class, 'Config Snapshots', 'fa fa-file-code')
            ->setPermission('administration.config.view');
        yield MenuItem::linkTo(AdministrationChangeRequestCrudController::class, 'Change Requests', 'fa fa-code-branch')
            ->setPermission('administration.config.view');
        yield MenuItem::linkTo(AdministrationOperationRunCrudController::class, 'Operation Runs', 'fa fa-list-check')
            ->setPermission('administration.operation.view');
        yield MenuItem::linkTo(AdministrationOperationEventCrudController::class, 'Operation Events', 'fa fa-clock-rotate-left')
            ->setPermission('administration.operation.view');
        yield MenuItem::linkTo(AdministrationOperationArtifactCrudController::class, 'Operation Artifacts', 'fa fa-box-archive')
            ->setPermission('administration.operation.view');
        yield MenuItem::linkToRoute('Launch Operations', 'fa fa-play', 'administration_operations')
            ->setPermission('administration.operation.view');

        yield MenuItem::section('Connected Components');
        yield MenuItem::linkToRoute('Connected Overview', 'fa fa-layer-group', 'administration_connected_component_overview')
            ->setPermission('administration.connected_component.overview.view');
        yield MenuItem::linkToRoute('Connected Readiness', 'fa fa-signal', 'administration_connected_component_readiness')
            ->setPermission('administration.connected_component.readiness.view');
        yield MenuItem::linkToRoute('Connected Remediation', 'fa fa-screwdriver-wrench', 'administration_connected_component_remediation')
            ->setPermission('administration.connected_component.remediation.view');
        yield MenuItem::linkToRoute('Connected Work Plan', 'fa fa-list-check', 'administration_connected_component_work_plan')
            ->setPermission('administration.connected_component.work_plan.view');
        yield MenuItem::linkToRoute('Connected Execution Plan', 'fa fa-list-check', 'administration_connected_component_execution_plan')
            ->setPermission('administration.connected_component.execution_plan.view');
        yield MenuItem::linkToRoute('Connected Capability Matrix', 'fa fa-table-cells-large', 'administration_connected_component_capability_matrix')
            ->setPermission('administration.connected_component.capability_matrix.view');
        yield MenuItem::linkToRoute('Connected Contract Matrix', 'fa fa-plug-circle-check', 'administration_connected_component_contract_matrix')
            ->setPermission('administration.connected_component.contract_matrix.view');
        yield MenuItem::linkToRoute('Connected Health', 'fa fa-heart-pulse', 'administration_connected_component_health')
            ->setPermission('administration.connected_component.health.view');
        yield MenuItem::linkToRoute('Connected Diagnostics', 'fa fa-triangle-exclamation', 'administration_connected_component_diagnostics')
            ->setPermission('administration.connected_component.diagnostics.view');
        yield MenuItem::linkToRoute('Rolling Permission Catalog', 'fa fa-shield', 'administration_rolling_permission_catalog')
            ->setPermission('administration.rolling.permission_catalog.view');
        yield MenuItem::linkToRoute('Managing Field Access Catalog', 'fa fa-table-list', 'administration_managing_field_access_catalog')
            ->setPermission('administration.rolling.permission_catalog.view');
        yield MenuItem::linkToRoute('Managing Field Access Matrix', 'fa fa-table-cells', 'administration_managing_field_access_matrix')
            ->setPermission('administration.rolling.permission_catalog.view');
        yield MenuItem::linkToRoute('Managing Field View Profiles', 'fa fa-eye', 'administration_managing_field_view_profiles')
            ->setPermission('administration.rolling.permission_catalog.view');
        yield MenuItem::linkToRoute('Managing Field View Profile Priority', 'fa fa-layer-group', 'administration_managing_field_view_profile_priority')
            ->setPermission('administration.rolling.permission_catalog.view');
        yield MenuItem::linkToRoute('Managing Field Visibility Explanation', 'fa fa-magnifying-glass-chart', 'administration_managing_field_visibility_explanation')
            ->setPermission('administration.rolling.permission_catalog.view');
        yield MenuItem::linkToRoute('Inspect Managing Field Visibility', 'fa fa-magnifying-glass', 'administration_managing_field_visibility_inspection')
            ->setPermission('administration.rolling.permission_catalog.view');
        yield MenuItem::linkToRoute('Rolling-backed Managing Access Readiness', 'fa fa-shield-halved', 'administration_managing_rolling_field_access_readiness')
            ->setPermission('administration.rolling.permission_catalog.view');
        yield MenuItem::linkToRoute('Review Managing Field View Profile', 'fa fa-eye-slash', 'administration_managing_field_view_profile_edit')
            ->setPermission('administration.rolling.permission_catalog.view');
        yield MenuItem::linkToRoute('Apply Managing Field View Profile', 'fa fa-check-to-slot', 'administration_managing_field_view_profile_apply')
            ->setPermission('administration.rolling.permission_catalog.view');
        yield MenuItem::linkToRoute('Managing Field Access Mutations', 'fa fa-sliders', 'administration_managing_field_access_mutations')
            ->setPermission('administration.rolling.acl_mutation.review.view');
        yield MenuItem::linkToRoute('Apply Managing Field Access Review', 'fa fa-check-to-slot', 'administration_managing_field_access_mutation_apply')
            ->setPermission('administration.rolling.acl_mutation.apply.view');
        yield MenuItem::linkToRoute('Rolling Subject Access Report', 'fa fa-user-check', 'administration_rolling_subject_access_report')
            ->setPermission('administration.rolling.subject_access_report.view');
        yield MenuItem::linkToRoute('Accessing Accounts', 'fa fa-user-shield', 'administration_accessing_accounts')
            ->setPermission('administration.accessing.account.view');
        yield MenuItem::linkToRoute('Accessing Account Actions', 'fa fa-user-lock', 'administration_accessing_account_actions')
            ->setPermission('administration.accessing.account_action.view');
        yield MenuItem::linkToRoute('Accessing Action Audit', 'fa fa-clock-rotate-left', 'administration_accessing_account_action_audit')
            ->setPermission('administration.accessing.account_action.audit.view');
        yield MenuItem::linkToRoute('Rolling ACL Mutations', 'fa fa-diagram-project', 'administration_rolling_acl_mutations')
            ->setPermission('administration.rolling.acl_mutation.review.view');
        yield MenuItem::linkToRoute('Apply Rolling ACL Review', 'fa fa-check-to-slot', 'administration_rolling_acl_mutation_apply')
            ->setPermission('administration.rolling.acl_mutation.apply.view');
        yield MenuItem::linkToRoute('Rolling ACL Apply Report', 'fa fa-chart-simple', 'administration_rolling_acl_mutation_apply_report')
            ->setPermission('administration.rolling.acl_mutation.apply.view');
        yield MenuItem::linkToRoute('Rolling ACL Execution Report', 'fa fa-timeline', 'administration_rolling_acl_mutation_execution_report')
            ->setPermission('administration.rolling.acl.execution_report.view');
        yield MenuItem::linkTo(AdministrationAclMutationReviewRecordCrudController::class, 'ACL Mutation Reviews', 'fa fa-clipboard-check')
            ->setPermission('administration.rolling.acl_mutation.review.view');
        yield MenuItem::linkTo(AdministrationAclMutationApplyRecordCrudController::class, 'ACL Mutation Apply Records', 'fa fa-shield-circle-check')
            ->setPermission('administration.rolling.acl_mutation.apply.view');
        yield MenuItem::linkTo(RollingRoleCrudController::class, 'Rolling Roles', 'fa fa-user-shield')
            ->setPermission('administration.rolling.subject_access_report.view');
        yield MenuItem::linkTo(RollingPermissionCrudController::class, 'Rolling Permissions', 'fa fa-key')
            ->setPermission('administration.rolling.permission_catalog.view');
        yield MenuItem::linkTo(RollingRolePermissionCrudController::class, 'Rolling Role Permissions', 'fa fa-link')
            ->setPermission('administration.rolling.subject_access_report.view');
        yield MenuItem::linkTo(RollingSubjectRoleAssignmentCrudController::class, 'Rolling Subject Assignments', 'fa fa-users-gear')
            ->setPermission('administration.rolling.subject_access_report.view');
        yield MenuItem::linkTo(RollingAclRuleCrudController::class, 'Rolling ACL Rules', 'fa fa-scale-balanced')
            ->setPermission('administration.rolling.subject_access_report.view');
        yield MenuItem::linkTo(AdministrationAccountActionRequestRecordCrudController::class, 'Account Action Requests', 'fa fa-user-gear')
            ->setPermission('administration.accessing.account_action.audit.view');

        yield MenuItem::section('Credentials');
        yield MenuItem::linkTo(AdministrationCredentialDefinitionCrudController::class, 'Credential Definitions', 'fa fa-key')
            ->setPermission('administration.config.view');
        yield MenuItem::linkTo(AdministrationCredentialStateCrudController::class, 'Credential States', 'fa fa-shield-halved')
            ->setPermission('administration.config.view');

        yield MenuItem::section('Audit');
        yield MenuItem::linkTo(AdministrationAuditEventCrudController::class, 'Audit Events', 'fa fa-clock-rotate-left')
            ->setPermission('administration.accessing.account_action.audit.view');
    }
}
