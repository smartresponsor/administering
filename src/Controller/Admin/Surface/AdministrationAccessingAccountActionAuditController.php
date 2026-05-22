<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use App\Administering\ServiceInterface\Accessing\AdministrationAccountActionAuditProjectionProviderInterface;
use App\Administering\Value\Accessing\AdministrationAccountActionAuditProjection;
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
        $summaryHtml = sprintf(
            '<pre>%s</pre>',
            htmlspecialchars(json_encode($summary->toSafeArray(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        );

        $rows = array_map(
            static fn (AdministrationAccountActionAuditProjection $projection): string => sprintf(
                '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                htmlspecialchars($projection->createdAt()->format(DATE_ATOM), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($projection->action(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($projection->accountReference(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($projection->requestedBySubject(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($projection->resultStatus(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($projection->safeMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ),
            $this->auditProjectionProvider->recent(100),
        );

        return new Response(sprintf(
            '<h1>Accessing Account Action Audit</h1><p>Metadata-only projection. Password hashes, TOTP secrets, recovery codes, reset tokens, sessions, and verification internals are never exposed here.</p><h2>Summary</h2>%s<table><thead><tr><th>Created</th><th>Action</th><th>Account</th><th>Requested by</th><th>Status</th><th>Message</th></tr></thead><tbody>%s</tbody></table>',
            $summaryHtml,
            implode('', $rows),
        ));
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
