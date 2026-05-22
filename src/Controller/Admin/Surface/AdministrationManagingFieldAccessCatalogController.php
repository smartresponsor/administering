<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use App\Administering\ServiceInterface\Rolling\AdministrationFieldAccessCatalogProviderInterface;
use App\Administering\Value\Rolling\AdministrationFieldAccessCatalogItem;
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

        $rows = array_map(fn (AdministrationFieldAccessCatalogItem $item): string => $this->renderRow($item), $this->catalogProvider->catalogItems());

        return new Response(sprintf(
            '<h1>Managing Field Access Catalog</h1><p>Read-only control-plane metadata for Managing field access and profile permissions. Rolling owns effective access decisions; Managing enforces final EasyAdmin field filtering.</p><table><thead><tr><th>Permission</th><th>Label</th><th>Group</th><th>Scopes</th><th>Sensitive</th><th>Rolling</th></tr></thead><tbody>%s</tbody></table>',
            implode('', $rows),
        ));
    }

    private function renderRow(AdministrationFieldAccessCatalogItem $item): string
    {
        return sprintf(
            '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
            $this->escape($item->permissionKey),
            $this->escape($item->label),
            $this->escape($item->controlPlaneGroup),
            $this->escape(implode(', ', $item->scopes)),
            $item->sensitive ? 'yes' : 'no',
            $item->registeredInRolling ? 'registered' : 'missing',
        );
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
