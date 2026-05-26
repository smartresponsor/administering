<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Managing;

use App\Managing\Value\Administration\ManagingFieldVisibilityExplanationScenario;
use App\Managing\Value\Administration\ManagingFieldVisibilityExplanationStep;

interface AdministrationFieldVisibilityExplanationCatalogProviderInterface
{
    /** @return list<ManagingFieldVisibilityExplanationStep> */
    public function explanationSteps(): array;

    /** @return list<ManagingFieldVisibilityExplanationScenario> */
    public function diagnosticScenarios(): array;
}
