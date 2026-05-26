<?php

declare(strict_types=1);

namespace App\Administering\Catalog\Admin;

use App\Administering\CatalogInterface\Admin\AdministrationServiceToolCatalogInterface;
use App\Administering\ServiceInterface\Admin\AdministrationOwnerConfigurationToolProviderInterface;
use App\Administering\ValidatorInterface\Admin\AdministrationOwnerConfigurationToolDefinitionValidatorInterface;
use App\Administering\Value\Admin\AdministrationOwnerConfigurationToolDefinition;
use App\Administering\Value\Admin\AdministrationServiceTool;

/**
 * Merges Administering-owned legacy tools with owner-provided configuration tools.
 *
 * This is the transition point from Administering-owned tool files toward the
 * cleaner owner-side prefix architecture. Internal filesystem scanning remains
 * supported, while neighboring components can start exposing their own tools via
 * tagged owner providers.
 */
final readonly class CompositeAdministrationServiceToolCatalog implements AdministrationServiceToolCatalogInterface
{
    /** @param iterable<AdministrationOwnerConfigurationToolProviderInterface> $ownerToolProviders */
    public function __construct(
        private FilesystemAdministrationServiceToolCatalog $internalCatalog,
        private AdministrationOwnerConfigurationToolDefinitionValidatorInterface $ownerToolDefinitionValidator,
        private iterable $ownerToolProviders = [],
    ) {
    }

    public function tools(): array
    {
        $tools = $this->internalCatalog->tools();

        foreach ($this->ownerToolProviders as $provider) {
            foreach ($provider->tools() as $definition) {
                if ($this->hasMaterializationError($provider, $definition)) {
                    continue;
                }

                $tools[] = $this->fromOwnerDefinition($provider, $definition);
            }
        }

        return $this->sortBySectionAndKey($tools);
    }

    public function toolsForSection(string $section): array
    {
        $tools = $this->internalCatalog->toolsForSection($section);

        foreach ($this->ownerToolProviders as $provider) {
            if (0 !== strcasecmp($provider->componentKey(), $section) && 0 !== strcasecmp($provider->componentToken(), $section)) {
                continue;
            }

            foreach ($provider->tools() as $definition) {
                if ($this->hasMaterializationError($provider, $definition)) {
                    continue;
                }

                $tools[] = $this->fromOwnerDefinition($provider, $definition);
            }
        }

        return $this->sortBySectionAndKey($tools);
    }

    private function hasMaterializationError(AdministrationOwnerConfigurationToolProviderInterface $provider, AdministrationOwnerConfigurationToolDefinition $definition): bool
    {
        foreach ($this->ownerToolDefinitionValidator->validate($provider, $definition) as $violation) {
            if ($violation->isError()) {
                return true;
            }
        }

        return false;
    }

    private function fromOwnerDefinition(AdministrationOwnerConfigurationToolProviderInterface $provider, AdministrationOwnerConfigurationToolDefinition $definition): AdministrationServiceTool
    {
        return new AdministrationServiceTool(
            section: $definition->componentKey,
            directionToken: $definition->componentKey,
            toolSlug: $definition->toolSlug,
            toolKey: $definition->toolKey(),
            serviceClass: $definition->serviceClass,
            shortName: $definition->serviceShortName,
            serviceFile: $definition->resolvedServiceFile(),
            label: $definition->label,
            kind: $definition->kind,
            operationType: $definition->operationType,
            checksum: $definition->resolvedChecksum(),
            formTypeClass: $definition->formTypeClass,
            formDataClass: $definition->formDataClass,
            executable: $definition->executable,
            primaryRouteName: $definition->primaryRouteName,
            primaryRouteLabel: $definition->primaryRouteLabel,
            sourceOwnership: 'owner_component',
            ownerComponentKey: $definition->componentKey,
            ownerComponentToken: $definition->componentToken,
            ownerProviderClass: $provider::class,
            ownerServiceClass: $definition->serviceClass,
            ownerSourceLabel: $definition->componentKey.' owner provider',
        );
    }

    /** @param list<AdministrationServiceTool> $tools */
    private function sortBySectionAndKey(array $tools): array
    {
        usort($tools, static function (AdministrationServiceTool $left, AdministrationServiceTool $right): int {
            return [$left->section, $left->toolKey] <=> [$right->section, $right->toolKey];
        });

        return $tools;
    }
}
