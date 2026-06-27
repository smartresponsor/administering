<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Connected;

use App\Administering\ServiceInterface\Connected\AdministrationConnectedComponentDiagnosticReportProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Exposes a metadata-only diagnostic register for connected administration components.
 */
final class AdministrationConnectedComponentDiagnosticReportController extends AbstractController
{
    #[Route('/ea/administering/component/diagnostic/report.json', name: 'administration_connected_component_diagnostics', methods: ['GET'])]
    public function __invoke(AdministrationConnectedComponentDiagnosticReportProviderInterface $provider): JsonResponse
    {
        $this->denyAccessUnlessGranted('administration.connected_component.diagnostics.view', 'administering:connected');

        return $this->json($provider->report()->toSafeArray());
    }
}
