<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Connected;

use App\Administering\ServiceInterface\Connected\AdministrationConnectedComponentCapabilityMatrixProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Exposes a metadata-only capability matrix for connected administration components.
 */
final class AdministrationConnectedComponentCapabilityMatrixController extends AbstractController
{
    #[Route('/ea/administering/component/capability/matrix.json', name: 'administration_connected_component_capability_matrix', methods: ['GET'])]
    public function __invoke(AdministrationConnectedComponentCapabilityMatrixProviderInterface $provider): JsonResponse
    {
        $this->denyAccessUnlessGranted('administration.connected_component.capability_matrix.view', 'administering:connected');

        return $this->json($provider->matrix()->toSafeArray());
    }
}
