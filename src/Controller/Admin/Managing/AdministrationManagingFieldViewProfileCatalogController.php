<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Managing;

use App\Administering\ServiceInterface\Managing\AdministrationFieldViewProfileCatalogProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Read-only Administering surface for Managing field view profile metadata.
 */
final class AdministrationManagingFieldViewProfileCatalogController extends AbstractController
{
    public function __construct(private readonly AdministrationFieldViewProfileCatalogProviderInterface $profileCatalogProvider)
    {
    }

    #[Route('/ea/managing/field-view-profiles', name: 'administration_managing_field_view_profiles')]
    public function __invoke(): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.permission_catalog.view', 'administering:managing-field-view-profiles');

        return $this->render('@Administering/administering/managing_field_view_profiles.html.twig', [
            'items' => $this->profileCatalogProvider->catalogItems(),
            'shapes' => $this->profileCatalogProvider->ruleShapes(),
        ]);
    }
}
