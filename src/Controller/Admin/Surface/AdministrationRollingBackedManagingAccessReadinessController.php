<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Read-only Administering readiness surface for Rolling-backed Managing field access.
 */
final class AdministrationRollingBackedManagingAccessReadinessController extends AbstractController
{
    public function __construct(
        private readonly \App\Administering\ServiceInterface\Managing\AdministrationRollingBackedManagingAccessReadinessProviderInterface $readinessProvider,
    ) {
    }

    #[Route('/ea/managing/rolling-field-access-readiness', name: 'administration_managing_rolling_field_access_readiness', methods: ['GET'])]
    public function __invoke(): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.permission_catalog.view', 'administering:managing-rolling-field-access-readiness');

        return $this->render('@Administering/administering/rolling_backed_managing_access_readiness.html.twig', [
            'report' => $this->readinessProvider->report(),
        ]);
    }
}
