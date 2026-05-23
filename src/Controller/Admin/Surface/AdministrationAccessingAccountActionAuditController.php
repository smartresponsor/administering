<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use App\Administering\ServiceInterface\Accessing\AdministrationAccountActionAuditProjectionProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Native metadata-only surface for Accessing controlled account action audit.
 */
final class AdministrationAccessingAccountActionAuditController extends AbstractController
{
    public function __construct(private readonly AdministrationAccountActionAuditProjectionProviderInterface $auditProjectionProvider)
    {
    }

    #[Route('/admin/accessing/account-action-audit', name: 'administration_accessing_account_action_audit', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('administration.accessing.account_action.audit.view', 'administering:accessing');

        $summary = $this->auditProjectionProvider->summary(200);

        return $this->render('@Administering/administering/accessing_account_action_audit.html.twig', [
            'summary_json' => json_encode($summary->toSafeArray(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
            'projections' => $this->auditProjectionProvider->recent(100),
        ]);
    }

    #[Route('/admin/accessing/account-action-audit/report.json', name: 'administration_accessing_account_action_audit_report', methods: ['GET'])]
    public function report(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('administration.accessing.account_action.audit.view', 'administering:accessing');

        return new JsonResponse($this->auditProjectionProvider->filteredReport(
            $request->query->getString('action') ?: null,
            $request->query->getString('status') ?: null,
            $request->query->getString('accountReference') ?: null,
            $request->query->getInt('limit', 100),
        )->toSafeArray());
    }
}
