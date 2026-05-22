<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use App\Administering\ServiceInterface\Connected\AdministrationConnectedComponentReadinessReportProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Exposes metadata-only readiness for Accessing and Rolling integration surfaces.
 */
final class AdministrationConnectedComponentReadinessController extends AbstractController
{
    #[Route('/admin/connected-components/readiness.json', name: 'administration_connected_component_readiness', methods: ['GET'])]
    public function __invoke(AdministrationConnectedComponentReadinessReportProviderInterface $provider): JsonResponse
    {
        $this->denyAccessUnlessGranted('administration.connected_component.readiness.view', 'administering:connected');

        return $this->json($provider->report()->toSafeArray());
    }
}
