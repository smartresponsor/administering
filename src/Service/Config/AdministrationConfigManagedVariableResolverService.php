<?php

declare(strict_types=1);

namespace App\Administering\Service\Config;

use App\Administering\Locator\Config\AdministrationConfigToolServiceLocator;
use App\Administering\ServiceInterface\Config\ManagedConfigVariablesProviderInterface;
use App\Administering\Value\Config\ConfigVariable;

final readonly class AdministrationConfigManagedVariableResolverService
{
    public function __construct(private AdministrationConfigToolServiceLocator $toolServiceLocator)
    {
    }

    /** @return list<ConfigVariable> */
    public function variablesForTool(string $applicationCode, string $toolCode): array
    {
        $toolService = $this->toolServiceLocator->forTool($applicationCode, $toolCode);
        if (!$toolService instanceof ManagedConfigVariablesProviderInterface) {
            return [];
        }

        return iterator_to_array($toolService->variables(), false);
    }

    public function hasManagedVariables(string $applicationCode, string $toolCode): bool
    {
        return [] !== $this->variablesForTool($applicationCode, $toolCode);
    }
}
