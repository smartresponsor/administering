<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Connected;

use App\Administering\ServiceInterface\Connected\AdministrationConnectedComponentRemediationPlanProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Exposes metadata-only remediation guidance for connected administration components.
 */
final class AdministrationConnectedComponentRemediationController extends AbstractController
{
    #[Route('/ea/administration/component/remediation.json', name: 'administration_connected_component_remediation', methods: ['GET'])]
    public function __invoke(AdministrationConnectedComponentRemediationPlanProviderInterface $provider): JsonResponse
    {
        $this->denyAccessUnlessGranted('administration.connected_component.remediation.view', 'administering:connected');

        return $this->json($provider->plan()->toSafeArray());
    }
}
