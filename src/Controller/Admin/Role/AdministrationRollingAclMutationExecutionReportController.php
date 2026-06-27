<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Role;

use App\Administering\ServiceInterface\Rolling\AdministrationRollingAclMutationExecutionReportProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Metadata-only report endpoint for Rolling ACL mutation execution events.
 */
final class AdministrationRollingAclMutationExecutionReportController extends AbstractController
{
    public function __construct(private readonly AdministrationRollingAclMutationExecutionReportProviderInterface $reportProvider)
    {
    }

    #[Route('/ea/role/execution/report.json', name: 'administration_rolling_acl_mutation_execution_report', methods: ['GET'])]
    public function report(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('administration.rolling.acl.execution_report.view', 'administering:rolling');

        return new JsonResponse($this->reportProvider->report(
            $request->query->getString('mutationType') ?: null,
            $request->query->getString('status') ?: null,
            $request->query->getString('subjectIdentifier') ?: null,
            $request->query->getInt('limit', 100),
        )->toSafeArray());
    }
}
