<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use App\Administering\ServiceInterface\Connected\AdministrationConnectedComponentOverviewProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Exposes a metadata-only integration overview for Administering connected components.
 */
final class AdministrationConnectedComponentOverviewController extends AbstractController
{
    #[Route('/admin/connected-components/overview.json', name: 'administration_connected_component_overview', methods: ['GET'])]
    public function __invoke(AdministrationConnectedComponentOverviewProviderInterface $provider): JsonResponse
    {
        $this->denyAccessUnlessGranted('administration.connected_component.overview.view', 'administering:connected');

        return $this->json($provider->overview()->toSafeArray());
    }
}
