<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use App\Administering\ServiceInterface\Rolling\AdministrationPermissionCatalogProviderInterface;
use App\Administering\Value\Rolling\AdministrationPermissionDescriptor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Native Symfony surface for the Rolling permission catalog visible from Administering.
 */
final class AdministrationPermissionCatalogController extends AbstractController
{
    public function __construct(private readonly AdministrationPermissionCatalogProviderInterface $permissionCatalogProvider)
    {
    }

    #[Route('/admin/rolling/permission-catalog', name: 'administration_rolling_permission_catalog')]
    public function __invoke(): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.permission_catalog.view', 'administering:rolling');

        $rows = array_map(
            static fn (AdministrationPermissionDescriptor $descriptor): string => sprintf(
                '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                htmlspecialchars($descriptor->key(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($descriptor->label(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($descriptor->category(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars(implode(', ', $descriptor->scopes()), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                $descriptor->sensitive() ? 'yes' : 'no',
            ),
            $this->permissionCatalogProvider->descriptors(),
        );

        return new Response(sprintf(
            '<h1>Rolling Permission Catalog</h1><p>Safe metadata exported by Rolling for Administering visualization.</p><table><thead><tr><th>Key</th><th>Label</th><th>Category</th><th>Scopes</th><th>Sensitive</th></tr></thead><tbody>%s</tbody></table>',
            implode('', $rows),
        ));
    }
}
