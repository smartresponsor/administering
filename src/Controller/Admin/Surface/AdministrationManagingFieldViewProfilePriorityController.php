<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use App\Administering\ServiceInterface\Managing\AdministrationFieldViewProfileCatalogProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Read-only hierarchy view for Managing field access and view profile priority.
 */
final class AdministrationManagingFieldViewProfilePriorityController extends AbstractController
{
    public function __construct(private readonly AdministrationFieldViewProfileCatalogProviderInterface $profileCatalogProvider)
    {
    }

    #[Route('/admin/managing/field-view-profile-priority', name: 'administration_managing_field_view_profile_priority')]
    public function __invoke(): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.permission_catalog.view', 'administering:managing-field-view-profile-priority');

        return $this->render('@Administering/administering/managing_field_view_profile_priority.html.twig', [
            'rows' => $this->profileCatalogProvider->priorityRows(),
        ]);
    }
}
