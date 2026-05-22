<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use App\Administering\ServiceInterface\Rolling\AdministrationFieldViewProfileCatalogProviderInterface;
use App\Administering\Value\Rolling\AdministrationFieldViewProfilePriorityRow;
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

        $rows = array_map(fn (AdministrationFieldViewProfilePriorityRow $row): string => $this->renderRow($row), $this->profileCatalogProvider->priorityRows());

        return new Response(sprintf(
            '<h1>Managing Field View Profile Priority</h1><p>Read-only priority chain for field access and personal/default presentation profiles. User preferences never create access and cannot override deny decisions.</p><table><thead><tr><th>Priority</th><th>Layer</th><th>Owner</th><th>Decision</th><th>Can Override</th><th>Notes</th></tr></thead><tbody>%s</tbody></table>',
            implode('', $rows),
        ));
    }

    private function renderRow(AdministrationFieldViewProfilePriorityRow $row): string
    {
        return sprintf(
            '<tr><td>%d</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
            $row->priority,
            $this->escape($row->layer),
            $this->escape($row->ownerComponent),
            $this->escape($row->decisionType),
            $this->escape($row->canOverride),
            $this->escape($row->notes),
        );
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
