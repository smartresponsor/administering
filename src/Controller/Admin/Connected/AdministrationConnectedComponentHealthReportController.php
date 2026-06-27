<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Connected;

use App\Administering\ServiceInterface\Connected\AdministrationConnectedComponentHealthReportProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Exposes a metadata-only health report for connected administration components.
 */
final class AdministrationConnectedComponentHealthReportController extends AbstractController
{
    #[Route('/ea/component/health/report.json', name: 'administration_connected_component_health', methods: ['GET'])]
    public function __invoke(AdministrationConnectedComponentHealthReportProviderInterface $provider): JsonResponse
    {
        $this->denyAccessUnlessGranted('administration.connected_component.health.view', 'administering:connected');

        return $this->json($provider->report()->toSafeArray());
    }
}
