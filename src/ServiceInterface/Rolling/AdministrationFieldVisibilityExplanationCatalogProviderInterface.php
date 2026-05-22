<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Rolling;

use App\Administering\Value\Rolling\AdministrationFieldVisibilityExplanationScenario;
use App\Administering\Value\Rolling\AdministrationFieldVisibilityExplanationStep;

interface AdministrationFieldVisibilityExplanationCatalogProviderInterface
{
    /** @return list<AdministrationFieldVisibilityExplanationStep> */
    public function explanationSteps(): array;

    /** @return list<AdministrationFieldVisibilityExplanationScenario> */
    public function diagnosticScenarios(): array;
}
