<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Managing;

use App\Administering\Value\Managing\ManagingFieldVisibilityExplanationScenario;
use App\Administering\Value\Managing\ManagingFieldVisibilityExplanationStep;

interface AdministrationFieldVisibilityExplanationCatalogProviderInterface
{
    /** @return list<ManagingFieldVisibilityExplanationStep> */
    public function explanationSteps(): array;

    /** @return list<ManagingFieldVisibilityExplanationScenario> */
    public function diagnosticScenarios(): array;
}
