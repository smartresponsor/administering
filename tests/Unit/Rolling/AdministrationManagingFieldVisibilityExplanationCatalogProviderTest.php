<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Rolling;

use App\Administering\Provider\Managing\AdministrationManagingFieldVisibilityExplanationCatalogProvider;
use App\Administering\Value\Managing\AdministrationManagingFieldVisibilityExplanationStep;
use PHPUnit\Framework\TestCase;

final class AdministrationManagingFieldVisibilityExplanationCatalogProviderTest extends TestCase
{
    public function testProvidesAxisAlignedTerminalDenyAndUserProfileExplanation(): void
    {
        $provider = new AdministrationManagingFieldVisibilityExplanationCatalogProvider();

        $steps = $provider->explanationSteps();
        $labels = array_map(static fn (AdministrationManagingFieldVisibilityExplanationStep $step): string => $step->label, $steps);
        $axisByLabel = [];

        foreach ($steps as $step) {
            $axisByLabel[$step->label] = $step->axis;
        }

        self::assertContains('External field-value access decision', $labels);
        self::assertContains('User personal profile', $labels);
        self::assertContains('Final EasyAdmin emission', $labels);
        self::assertSame(AdministrationManagingFieldVisibilityExplanationStep::AXIS_ACCESS, $axisByLabel['External field-value access decision']);
        self::assertSame(AdministrationManagingFieldVisibilityExplanationStep::AXIS_PRESENTATION, $axisByLabel['User personal profile']);
    }

    public function testDiagnosticScenariosAreAxisSeparatedAndPresentationSafe(): void
    {
        $provider = new AdministrationManagingFieldVisibilityExplanationCatalogProvider();
        $axes = [];

        foreach ($provider->diagnosticScenarios() as $scenario) {
            self::assertNotSame('', $scenario->key);
            self::assertNotSame('', $scenario->operatorAction);
            self::assertNotSame([], $scenario->matchingAxes);
            $axes = array_values(array_unique(array_merge($axes, $scenario->matchingAxes)));
        }

        self::assertContains(AdministrationManagingFieldVisibilityExplanationStep::AXIS_ACCESS, $axes);
        self::assertContains(AdministrationManagingFieldVisibilityExplanationStep::AXIS_PRESENTATION, $axes);
        self::assertContains(AdministrationManagingFieldVisibilityExplanationStep::AXIS_AVAILABILITY, $axes);
    }
}
