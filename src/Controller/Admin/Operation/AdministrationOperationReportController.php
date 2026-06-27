<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Operation;

use App\Administering\ServiceInterface\Operation\AdministrationOperationReportProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Native metadata-only endpoint for operation reports.
 */
final class AdministrationOperationReportController extends AbstractController
{
    public function __construct(private readonly AdministrationOperationReportProviderInterface $reportProvider)
    {
    }

    #[Route('/ea/operation/run/{operationKey}/report.json', name: 'administering_operation_report_json', methods: ['GET'])]
    public function __invoke(string $operationKey): JsonResponse
    {
        $this->denyAccessUnlessGranted('administration.operation.view', 'administering:operation');

        return $this->json($this->reportProvider->reportFor($operationKey)->toArray());
    }
}
