<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Rolling;

use App\Administering\Provider\Managing\AdministrationManagingFieldVisibilityExplanationCatalogProvider;
use App\Managing\Value\Administration\ManagingFieldVisibilityExplanationStep;
use PHPUnit\Framework\TestCase;

final class AdministrationManagingFieldVisibilityExplanationCatalogProviderTest extends TestCase
{
    public function testProvidesAxisAlignedTerminalDenyAndUserProfileExplanation(): void
    {
        $provider = new AdministrationManagingFieldVisibilityExplanationCatalogProvider();

        $steps = $provider->explanationSteps();
        $reasonCodes = array_map(static fn (ManagingFieldVisibilityExplanationStep $step): string => $step->reasonCodeExample, $steps);
        $axisByReason = [];

        foreach ($steps as $step) {
            $axisByReason[$step->reasonCodeExample] = $step->decisionAxis;
        }

        self::assertContains('rolling_field_value_access_denied', $reasonCodes);
        self::assertContains('user_profile_hidden', $reasonCodes);
        self::assertContains('final_renderable', $reasonCodes);
        self::assertSame(ManagingFieldVisibilityExplanationStep::AXIS_ACCESS, $axisByReason['rolling_field_value_access_denied']);
        self::assertSame(ManagingFieldVisibilityExplanationStep::AXIS_PRESENTATION, $axisByReason['user_profile_hidden']);
    }

    public function testDiagnosticScenariosAreAxisSeparatedAndPresentationSafe(): void
    {
        $provider = new AdministrationManagingFieldVisibilityExplanationCatalogProvider();
        $axes = [];

        foreach ($provider->diagnosticScenarios() as $scenario) {
            self::assertNotSame('', $scenario->scenarioKey);
            self::assertNotSame('', $scenario->safetyNote);
            self::assertNotSame([], $scenario->trace);
            $axes[] = $scenario->decisionAxis;
        }

        self::assertContains(ManagingFieldVisibilityExplanationStep::AXIS_ACCESS, $axes);
        self::assertContains(ManagingFieldVisibilityExplanationStep::AXIS_PRESENTATION, $axes);
        self::assertContains(ManagingFieldVisibilityExplanationStep::AXIS_AVAILABILITY, $axes);
    }
}
