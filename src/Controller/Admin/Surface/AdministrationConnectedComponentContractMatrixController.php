<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use App\Administering\ServiceInterface\Connected\AdministrationConnectedComponentContractMatrixProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Exposes a metadata-only contract matrix for connected administration components.
 */
final class AdministrationConnectedComponentContractMatrixController extends AbstractController
{
    #[Route('/ea/connected-components/contract-matrix.json', name: 'administration_connected_component_contract_matrix', methods: ['GET'])]
    public function __invoke(AdministrationConnectedComponentContractMatrixProviderInterface $provider): JsonResponse
    {
        $this->denyAccessUnlessGranted('administration.connected_component.contract_matrix.view', 'administering:connected');

        return $this->json($provider->matrix()->toSafeArray());
    }
}
