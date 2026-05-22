<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use App\Administering\ServiceInterface\Rolling\AdministrationFieldAccessCatalogProviderInterface;
use App\Administering\Value\Rolling\AdministrationFieldAccessMatrixRow;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Read-only matrix that explains the responsibility hierarchy for Managing field access.
 */
final class AdministrationManagingFieldAccessMatrixController extends AbstractController
{
    public function __construct(private readonly AdministrationFieldAccessCatalogProviderInterface $catalogProvider)
    {
    }

    #[Route('/admin/managing/field-access-matrix', name: 'administration_managing_field_access_matrix')]
    public function __invoke(): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.permission_catalog.view', 'administering:managing-field-access');

        $rows = array_map(fn (AdministrationFieldAccessMatrixRow $row): string => $this->renderRow($row), $this->catalogProvider->matrixRows());

        return new Response(sprintf(
            '<h1>Managing Field Access Matrix</h1><p>Read-only hierarchy before mutation/apply workflows are introduced. User preferences can only narrow already allowed fields.</p><table><thead><tr><th>Priority</th><th>Layer</th><th>Owner</th><th>Decision</th><th>Effect</th><th>Notes</th></tr></thead><tbody>%s</tbody></table>',
            implode('', $rows),
        ));
    }

    private function renderRow(AdministrationFieldAccessMatrixRow $row): string
    {
        return sprintf(
            '<tr><td>%d</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
            $row->priority,
            $this->escape($row->layer),
            $this->escape($row->ownerComponent),
            $this->escape($row->decisionType),
            $this->escape($row->effect),
            $this->escape($row->notes),
        );
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
