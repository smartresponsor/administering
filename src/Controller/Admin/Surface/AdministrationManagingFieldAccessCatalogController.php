<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use App\Administering\ServiceInterface\Managing\AdministrationFieldAccessCatalogProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Read-only Administering surface for Managing field access permission metadata.
 */
final class AdministrationManagingFieldAccessCatalogController extends AbstractController
{
    public function __construct(private readonly AdministrationFieldAccessCatalogProviderInterface $catalogProvider)
    {
    }

    #[Route('/admin/managing/field-access-catalog', name: 'administration_managing_field_access_catalog')]
    public function __invoke(): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.permission_catalog.view', 'administering:managing-field-access');

        return $this->render('@Administering/administering/managing_field_access_catalog.html.twig', [
            'items' => $this->catalogProvider->catalogItems(),
        ]);
    }
}
