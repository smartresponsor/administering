<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use App\Administering\ServiceInterface\Rolling\AdministrationFieldVisibilityExplanationCatalogProviderInterface;
use App\Administering\Value\Rolling\AdministrationFieldVisibilityExplanationScenario;
use App\Administering\Value\Rolling\AdministrationFieldVisibilityExplanationStep;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Read-only explanation surface for Managing field visibility diagnostics.
 */
final class AdministrationManagingFieldVisibilityExplanationController extends AbstractController
{
    public function __construct(private readonly AdministrationFieldVisibilityExplanationCatalogProviderInterface $catalogProvider)
    {
    }

    #[Route('/admin/managing/field-visibility-explanation', name: 'administration_managing_field_visibility_explanation')]
    public function __invoke(): Response
    {
        $this->denyAccessUnlessGranted(
            'administration.rolling.permission_catalog.view',
            'administering:managing-field-visibility-explanation',
        );

        return new Response(sprintf(
            '%s%s%s',
            '<h1>Managing Field Visibility Explanation</h1>'
            .'<p>Read-only diagnostic map for why a field is rendered, hidden, or denied.</p>'
            .'<p><strong>Decision axes:</strong> access, presentation, availability.</p>',
            $this->renderDecisionTraceTable(),
            $this->renderScenarioTable(),
        ));
    }

    private function renderDecisionTraceTable(): string
    {
        $rows = array_map(
            fn (AdministrationFieldVisibilityExplanationStep $step): string => $this->renderStep($step),
            $this->catalogProvider->explanationSteps(),
        );

        return '<h2>Decision Trace</h2><table><thead><tr><th>Priority</th><th>Stage</th><th>Owner</th>'
            .'<th>Axis</th><th>Effect</th><th>Terminal</th><th>Reason Example</th><th>Notes</th></tr></thead><tbody>'
            .implode('', $rows)
            .'</tbody></table>';
    }

    private function renderScenarioTable(): string
    {
        $rows = array_map(
            fn (AdministrationFieldVisibilityExplanationScenario $scenario): string => $this->renderScenario($scenario),
            $this->catalogProvider->diagnosticScenarios(),
        );

        return '<h2>Diagnostic Scenarios</h2><table><thead><tr><th>Scenario</th><th>Axis</th><th>Final Decision</th>'
            .'<th>Trace</th><th>Safety</th></tr></thead><tbody>'
            .implode('', $rows)
            .'</tbody></table>';
    }

    private function renderStep(AdministrationFieldVisibilityExplanationStep $step): string
    {
        return sprintf(
            '<tr><td>%d</td><td>%s</td><td>%s</td><td><code>%s</code></td><td>%s</td><td>%s</td><td><code>%s</code></td><td>%s</td></tr>',
            $step->priority,
            $this->escape($step->stage),
            $this->escape($step->ownerComponent),
            $this->escape($step->decisionAxis),
            $this->escape($step->decisionEffect),
            $this->escape($step->terminalBehavior),
            $this->escape($step->reasonCodeExample),
            $this->escape($step->notes),
        );
    }

    private function renderScenario(AdministrationFieldVisibilityExplanationScenario $scenario): string
    {
        return sprintf(
            '<tr><td><strong>%s</strong><br><code>%s</code></td><td><code>%s</code></td><td>%s</td><td><code>%s</code></td><td>%s</td></tr>',
            $this->escape($scenario->label),
            $this->escape($scenario->scenarioKey),
            $this->escape($scenario->decisionAxis),
            $this->escape($scenario->finalDecision),
            $this->escape(implode(' → ', $scenario->trace)),
            $this->escape($scenario->safetyNote),
        );
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
