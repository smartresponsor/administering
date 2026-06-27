<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use App\Administering\ServiceInterface\Rolling\AdministrationAclMutationApplyReportProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Metadata-only report surface for Rolling ACL apply attempts.
 */
final class AdministrationRollingAclMutationApplyReportController extends AbstractController
{
    public function __construct(private readonly AdministrationAclMutationApplyReportProviderInterface $reportProvider)
    {
    }

    #[Route('/ea/role/mutation/apply/report.json', name: 'administration_rolling_acl_mutation_apply_report', methods: ['GET'])]
    public function report(): JsonResponse
    {
        $this->denyAccessUnlessGranted('administration.rolling.acl_mutation.apply.view', 'administering:rolling');

        $recent = array_map(
            static fn ($record): array => [
                'request_key' => $record->requestKey(),
                'mutation_type' => $record->mutationType(),
                'subject_identifier' => $record->subjectIdentifier(),
                'permission_or_role_key' => $record->permissionOrRoleKey(),
                'scope_key' => $record->scopeKey(),
                'requested_by_subject' => $record->requestedBySubject(),
                'status' => $record->status(),
                'succeeded' => $record->succeeded(),
                'safe_message' => $record->safeMessage(),
                'created_at' => $record->createdAt()->format(DATE_ATOM),
            ],
            $this->reportProvider->recent(25),
        );

        return new JsonResponse([
            'summary' => $this->reportProvider->summary()->toSafeArray(),
            'recent' => $recent,
        ]);
    }
}
