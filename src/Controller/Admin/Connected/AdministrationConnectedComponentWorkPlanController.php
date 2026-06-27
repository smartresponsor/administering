<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Connected;

use App\Administering\ServiceInterface\Connected\AdministrationConnectedComponentWorkPlanProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Exposes the safe actionable work plan for connected administration components.
 */
final class AdministrationConnectedComponentWorkPlanController extends AbstractController
{
    #[Route('/ea/administering/component/work/plan.json', name: 'administration_connected_component_work_plan', methods: ['GET'])]
    public function __invoke(AdministrationConnectedComponentWorkPlanProviderInterface $provider): JsonResponse
    {
        $this->denyAccessUnlessGranted('administration.connected_component.work_plan.view', 'administering:connected');

        return $this->json($provider->plan()->toSafeArray());
    }
}
