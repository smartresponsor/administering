<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Managing;

use App\Administering\Value\Managing\AdministrationManagingFieldVisibilityExplanationScenario;
use App\Administering\Value\Managing\AdministrationManagingFieldVisibilityExplanationStep;

interface AdministrationFieldVisibilityExplanationCatalogProviderInterface
{
    /** @return list<AdministrationManagingFieldVisibilityExplanationStep> */
    public function explanationSteps(): array;

    /** @return list<AdministrationManagingFieldVisibilityExplanationScenario> */
    public function diagnosticScenarios(): array;
}
