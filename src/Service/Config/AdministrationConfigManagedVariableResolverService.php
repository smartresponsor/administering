<?php

declare(strict_types=1);

namespace App\Administering\Service\Config;

use App\Administering\Locator\Config\AdministrationConfigToolServiceLocator;
use App\Configuring\ServiceInterface\Config\ManagedConfigVariablesProviderInterface;
use App\Configuring\Value\Config\ConfigVariable;

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

        return array_values(array_filter(
            iterator_to_array($toolService->managedVariables(), false),
            static fn (mixed $variable): bool => $variable instanceof ConfigVariable,
        ));
    }

    public function hasManagedVariables(string $applicationCode, string $toolCode): bool
    {
        return [] !== $this->variablesForTool($applicationCode, $toolCode);
    }
}
