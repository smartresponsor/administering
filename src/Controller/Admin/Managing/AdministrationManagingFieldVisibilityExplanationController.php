<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Managing;

use App\Administering\ServiceInterface\Managing\AdministrationFieldVisibilityExplanationCatalogProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Read-only explanation surface for Managing field visibility diagnostics.
 */
final class AdministrationManagingFieldVisibilityExplanationController extends AbstractController
{
    public function __construct(private readonly AdministrationFieldVisibilityExplanationCatalogProviderInterface $catalogProvider)
    {
    }

    #[Route('/ea/managing/field-visibility-explanation', name: 'administration_managing_field_visibility_explanation')]
    public function __invoke(): Response
    {
        $this->denyAccessUnlessGranted(
            'administration.rolling.permission_catalog.view',
            'administering:managing-field-visibility-explanation',
        );

        return $this->render('@Administering/administering/managing_field_visibility_explanation.html.twig', [
            'steps' => $this->catalogProvider->explanationSteps(),
            'scenarios' => $this->catalogProvider->diagnosticScenarios(),
        ]);
    }
}
