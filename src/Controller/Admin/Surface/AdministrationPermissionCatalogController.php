<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Native Symfony surface for the Administering-owned permission catalog snapshot.
 */
final class AdministrationPermissionCatalogController extends AbstractController
{
    /**
     * @var list<array{key: string, label: string, category: string, scopes: list<string>, sensitive: bool}>
     */
    private const PERMISSION_DESCRIPTORS = [
        ['key' => 'administration.dashboard.view', 'label' => 'View dashboard', 'category' => 'dashboard', 'scopes' => ['administering'], 'sensitive' => false],
        ['key' => 'administration.config.view', 'label' => 'View configuration', 'category' => 'configuration', 'scopes' => ['administering'], 'sensitive' => false],
        ['key' => 'administration.operation.view', 'label' => 'View operations', 'category' => 'operation', 'scopes' => ['administering'], 'sensitive' => false],
        ['key' => 'administration.accessing.account.view', 'label' => 'View account records', 'category' => 'accessing', 'scopes' => ['administering'], 'sensitive' => true],
        ['key' => 'administration.accessing.account_action.view', 'label' => 'View account actions', 'category' => 'accessing', 'scopes' => ['administering'], 'sensitive' => true],
        ['key' => 'administration.accessing.account_action.execute', 'label' => 'Record account action request', 'category' => 'accessing', 'scopes' => ['administering'], 'sensitive' => true],
        ['key' => 'administration.accessing.account_action.audit.view', 'label' => 'View account action audit', 'category' => 'accessing', 'scopes' => ['administering'], 'sensitive' => true],
        ['key' => 'administration.rolling.permission_catalog.view', 'label' => 'View permission catalog', 'category' => 'permission', 'scopes' => ['administering'], 'sensitive' => false],
        ['key' => 'administration.rolling.subject_access_report.view', 'label' => 'View subject access report', 'category' => 'permission', 'scopes' => ['administering'], 'sensitive' => true],
        ['key' => 'administration.rolling.acl_mutation.review.view', 'label' => 'View ACL mutation reviews', 'category' => 'permission', 'scopes' => ['administering'], 'sensitive' => true],
        ['key' => 'administration.rolling.acl_mutation.review', 'label' => 'Review ACL mutation', 'category' => 'permission', 'scopes' => ['administering'], 'sensitive' => true],
        ['key' => 'administration.rolling.acl_mutation.apply', 'label' => 'Apply ACL mutation', 'category' => 'permission', 'scopes' => ['administering'], 'sensitive' => true],
        ['key' => 'administration.rolling.acl_mutation.apply.view', 'label' => 'View ACL mutation apply log', 'category' => 'permission', 'scopes' => ['administering'], 'sensitive' => true],
        ['key' => 'administration.rolling.acl.execution_report.view', 'label' => 'View ACL execution report', 'category' => 'permission', 'scopes' => ['administering'], 'sensitive' => true],
        ['key' => 'administration.connected_component.overview.view', 'label' => 'View component overview', 'category' => 'component', 'scopes' => ['administering'], 'sensitive' => false],
        ['key' => 'administration.connected_component.readiness.view', 'label' => 'View component readiness', 'category' => 'component', 'scopes' => ['administering'], 'sensitive' => false],
        ['key' => 'administration.connected_component.remediation.view', 'label' => 'View component remediation', 'category' => 'component', 'scopes' => ['administering'], 'sensitive' => false],
        ['key' => 'administration.connected_component.work_plan.view', 'label' => 'View component work plan', 'category' => 'component', 'scopes' => ['administering'], 'sensitive' => false],
        ['key' => 'administration.connected_component.execution_plan.view', 'label' => 'View component execution plan', 'category' => 'component', 'scopes' => ['administering'], 'sensitive' => false],
        ['key' => 'administration.connected_component.capability_matrix.view', 'label' => 'View component capability matrix', 'category' => 'component', 'scopes' => ['administering'], 'sensitive' => false],
        ['key' => 'administration.connected_component.contract_matrix.view', 'label' => 'View component contract matrix', 'category' => 'component', 'scopes' => ['administering'], 'sensitive' => false],
        ['key' => 'administration.connected_component.health.view', 'label' => 'View component health', 'category' => 'component', 'scopes' => ['administering'], 'sensitive' => false],
        ['key' => 'administration.connected_component.diagnostics.view', 'label' => 'View component diagnostics', 'category' => 'component', 'scopes' => ['administering'], 'sensitive' => false],
    ];

    #[Route('/ea/role/permission/catalog', name: 'administration_rolling_permission_catalog')]
    public function __invoke(): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.permission_catalog.view', 'administering:rolling');

        return $this->render('@Administering/administering/rolling_permission_catalog.html.twig', [
            'descriptors' => self::PERMISSION_DESCRIPTORS,
        ]);
    }
}
