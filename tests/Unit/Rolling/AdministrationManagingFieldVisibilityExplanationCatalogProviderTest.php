<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Rolling;

use App\Administering\Service\Rolling\AdministrationManagingFieldVisibilityExplanationCatalogProvider;
use App\Administering\Value\Rolling\AdministrationFieldVisibilityExplanationStep;
use PHPUnit\Framework\TestCase;

final class AdministrationManagingFieldVisibilityExplanationCatalogProviderTest extends TestCase
{
    public function testProvidesAxisAlignedTerminalDenyAndUserProfileExplanation(): void
    {
        $provider = new AdministrationManagingFieldVisibilityExplanationCatalogProvider();

        $steps = $provider->explanationSteps();
        $reasonCodes = array_map(static fn (AdministrationFieldVisibilityExplanationStep $step): string => $step->reasonCodeExample, $steps);
        $axisByReason = [];

        foreach ($steps as $step) {
            $axisByReason[$step->reasonCodeExample] = $step->decisionAxis;
        }

        self::assertContains('rolling_field_value_access_denied', $reasonCodes);
        self::assertContains('user_profile_hidden', $reasonCodes);
        self::assertContains('final_renderable', $reasonCodes);
        self::assertSame(AdministrationFieldVisibilityExplanationStep::AXIS_ACCESS, $axisByReason['rolling_field_value_access_denied']);
        self::assertSame(AdministrationFieldVisibilityExplanationStep::AXIS_PRESENTATION, $axisByReason['user_profile_hidden']);
    }

    public function testDiagnosticScenariosAreAxisSeparatedAndPresentationSafe(): void
    {
        $provider = new AdministrationManagingFieldVisibilityExplanationCatalogProvider();
        $axes = [];

        foreach ($provider->diagnosticScenarios() as $scenario) {
            self::assertNotSame('', $scenario->scenarioKey);
            self::assertStringContainsString('access', strtolower($scenario->safetyNote));
            $axes[] = $scenario->decisionAxis;
        }

        self::assertContains(AdministrationFieldVisibilityExplanationStep::AXIS_ACCESS, $axes);
        self::assertContains(AdministrationFieldVisibilityExplanationStep::AXIS_PRESENTATION, $axes);
        self::assertContains(AdministrationFieldVisibilityExplanationStep::AXIS_AVAILABILITY, $axes);
    }
}
