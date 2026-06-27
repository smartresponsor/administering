<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Connected;

use App\Administering\ServiceInterface\Connected\AdministrationConnectedComponentExecutionPlanProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Exposes a metadata-only execution plan for connected administration components.
 */
final class AdministrationConnectedComponentExecutionPlanController extends AbstractController
{
    #[Route('/ea/component/execution/plan.json', name: 'administration_connected_component_execution_plan', methods: ['GET'])]
    public function __invoke(AdministrationConnectedComponentExecutionPlanProviderInterface $provider): JsonResponse
    {
        $this->denyAccessUnlessGranted('administration.connected_component.execution_plan.view', 'administering:connected');

        return $this->json($provider->plan()->toSafeArray());
    }
}
