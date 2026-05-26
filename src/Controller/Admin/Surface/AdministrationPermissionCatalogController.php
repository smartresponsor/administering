<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use App\Rolling\ServiceInterface\Administration\RollingAdministrationPermissionCatalogInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Native Symfony surface for the Rolling permission catalog visible from Administering.
 */
final class AdministrationPermissionCatalogController extends AbstractController
{
    public function __construct(private readonly RollingAdministrationPermissionCatalogInterface $permissionCatalog)
    {
    }

    #[Route('/admin/rolling/permission-catalog', name: 'administration_rolling_permission_catalog')]
    public function __invoke(): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.permission_catalog.view', 'administering:rolling');

        return $this->render('@Administering/administering/rolling_permission_catalog.html.twig', [
            'descriptors' => $this->permissionCatalog->descriptors(),
        ]);
    }
}
