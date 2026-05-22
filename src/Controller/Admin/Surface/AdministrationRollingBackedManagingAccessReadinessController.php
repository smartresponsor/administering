<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use App\Administering\ServiceInterface\Rolling\AdministrationRollingBackedManagingAccessReadinessProviderInterface;
use App\Administering\Value\Rolling\AdministrationRollingBackedManagingAccessReadinessItem;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Read-only Administering readiness surface for Rolling-backed Managing field access.
 */
final class AdministrationRollingBackedManagingAccessReadinessController extends AbstractController
{
    public function __construct(
        private readonly AdministrationRollingBackedManagingAccessReadinessProviderInterface $readinessProvider,
    ) {
    }

    #[Route('/admin/managing/rolling-field-access-readiness', name: 'administration_managing_rolling_field_access_readiness', methods: ['GET'])]
    public function __invoke(): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.permission_catalog.view', 'administering:managing-rolling-field-access-readiness');

        $report = $this->readinessProvider->report();
        $rows = array_map(fn (AdministrationRollingBackedManagingAccessReadinessItem $item): string => sprintf(
            '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td><code>%s</code></td><td>%s</td></tr>',
            $this->escape($item->key),
            $this->escape($item->label),
            $this->escape($item->status),
            $this->escape($item->owner),
            $this->escape($item->expectedValue),
            $this->escape($item->note),
        ), $report->items);

        return new Response(sprintf(<<<'HTML'
<h1>Rolling-backed Managing Field Access Readiness</h1>
<p>This read-only surface documents whether the ecosystem has the required contracts and host checklist for enabling Rolling-backed Managing field access.</p>
<ul>
  <li><strong>Expected backend:</strong> <code>%s</code></li>
  <li><strong>Expected failure effect:</strong> <code>%s</code></li>
  <li><strong>Permission key:</strong> <code>%s</code></li>
  <li><strong>Rolling decision contract:</strong> <code>%s</code></li>
  <li><strong>Managing adapter contract:</strong> <code>%s</code></li>
</ul>
<table border="1" cellpadding="6" cellspacing="0">
  <thead><tr><th>Key</th><th>Item</th><th>Status</th><th>Owner</th><th>Expected value</th><th>Note</th></tr></thead>
  <tbody>%s</tbody>
</table>
<h2>Safe host config target</h2>
<pre>managing:
  crud_field_external_access_backend: rolling
  crud_field_external_access_failure_effect: deny
  crud_field_external_access_permission_key: managing.field.view
  crud_field_external_access_rolling_decision_service: App\Rolling\ServiceInterface\Administration\RollingFieldAccessDecisionServiceInterface</pre>
<p>This surface does not mutate Rolling ACL, Managing configuration, or field profile storage.</p>
HTML,
            $this->escape($report->mode),
            $this->escape($report->failureEffect),
            $this->escape($report->permissionKey),
            $this->escape($report->rollingDecisionContract),
            $this->escape($report->managingAdapterContract),
            implode('', $rows),
        ));
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
