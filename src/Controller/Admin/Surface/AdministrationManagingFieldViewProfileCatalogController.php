<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use App\Administering\ServiceInterface\Rolling\AdministrationFieldViewProfileCatalogProviderInterface;
use App\Administering\Value\Rolling\AdministrationFieldViewProfileCatalogItem;
use App\Administering\Value\Rolling\AdministrationFieldViewProfileRuleShape;
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

    #[Route('/admin/managing/field-view-profiles', name: 'administration_managing_field_view_profiles')]
    public function __invoke(): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.permission_catalog.view', 'administering:managing-field-view-profiles');

        $profileRows = array_map(
            fn (AdministrationFieldViewProfileCatalogItem $item): string => $this->renderProfileRow($item),
            $this->profileCatalogProvider->catalogItems(),
        );
        $shapeRows = array_map(
            fn (AdministrationFieldViewProfileRuleShape $shape): string => $this->renderShapeRow($shape),
            $this->profileCatalogProvider->ruleShapes(),
        );

        return new Response(sprintf(
            '<h1>Managing Field View Profiles</h1><p>Read-only control-plane metadata for field presentation profiles. These profiles can only shape visible/hidden presentation inside access already allowed by Managing, Rolling, and Administering policy.</p><h2>Profile scopes</h2><table><thead><tr><th>Scope</th><th>Label</th><th>Owner</th><th>Storage</th><th>Boundary</th><th>Operations</th><th>Notes</th></tr></thead><tbody>%s</tbody></table><h2>Execution rule shape</h2><table><thead><tr><th>Section</th><th>Path</th><th>Rule Keys</th><th>Effect</th><th>Notes</th></tr></thead><tbody>%s</tbody></table>',
            implode('', $profileRows),
            implode('', $shapeRows),
        ));
    }

    private function renderProfileRow(AdministrationFieldViewProfileCatalogItem $item): string
    {
        return sprintf(
            '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
            $this->escape($item->scopeType),
            $this->escape($item->label),
            $this->escape($item->ownerComponent),
            $this->escape($item->storageOwner),
            $this->escape($item->securityBoundary),
            $this->escape(implode(', ', $item->allowedOperations)),
            $this->escape($item->notes),
        );
    }

    private function renderShapeRow(AdministrationFieldViewProfileRuleShape $shape): string
    {
        return sprintf(
            '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
            $this->escape($shape->section),
            $this->escape($shape->scopePath),
            $this->escape(implode(', ', $shape->ruleKeys)),
            $this->escape($shape->effect),
            $this->escape($shape->notes),
        );
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
