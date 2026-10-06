<?php

declare(strict_types=1);

namespace App\Administering\Validator\Admin;

use App\Administering\ServiceInterface\Config\AdministrationConfigVariableToolServiceInterface;
use App\Administering\ServiceInterface\Tool\AdministrationConfigurationToolProviderInterface;
use App\Administering\ValidatorInterface\Admin\AdministrationConfigurationToolDefinitionValidatorInterface;
use App\Administering\Value\Admin\AdministrationOwnerConfigurationToolViolation;
use App\Administering\Value\Tool\AdministrationConfigurationToolDefinition;

/**
 * Validates producer-side configuration tool definitions before materialization.
 *
 * This keeps Administering as a thin projection/orchestration shell while still
 * rejecting producer definitions that would create unstable SQLite/EasyAdmin rows.
 */
final readonly class AdministrationConfigurationToolDefinitionValidator implements AdministrationConfigurationToolDefinitionValidatorInterface
{
    public function validate(
        AdministrationConfigurationToolProviderInterface $provider,
        AdministrationConfigurationToolDefinition $definition,
    ): array {
        return [
            ...$this->identityViolations($provider, $definition),
            ...$this->serviceViolations($definition),
            ...$this->formConventionViolations($definition),
            ...$this->executableContractViolations($definition),
            ...$this->toolKeyViolations($definition),
        ];
    }

    /** @return list<AdministrationOwnerConfigurationToolViolation> */
    private function identityViolations(
        AdministrationConfigurationToolProviderInterface $provider,
        AdministrationConfigurationToolDefinition $definition,
    ): array {
        $violations = [];
        $componentKey = $definition->componentKey();
        $componentToken = $definition->componentToken();

        if ('' === trim($componentKey)) {
            $violations[] = $this->violation('error', $definition, 'componentKey', 'Component key must not be blank.');
        }

        if ('' === trim($componentToken) || 1 !== preg_match('/^[a-z][a-z0-9_]*$/', $componentToken)) {
            $violations[] = $this->violation('error', $definition, 'componentToken', 'Component token must be URL/key-safe lowercase snake text.', 'lowercase snake token', $componentToken);
        }

        if (0 !== strcasecmp($provider->componentKey(), $componentKey)) {
            $violations[] = $this->violation('error', $definition, 'componentKey', 'Definition component key must match provider component key.', $provider->componentKey(), $componentKey);
        }

        if (0 !== strcasecmp($provider->componentToken(), $componentToken)) {
            $violations[] = $this->violation('error', $definition, 'componentToken', 'Definition component token must match provider component token.', $provider->componentToken(), $componentToken);
        }

        return $violations;
    }

    /** @return list<AdministrationOwnerConfigurationToolViolation> */
    private function serviceViolations(AdministrationConfigurationToolDefinition $definition): array
    {
        $violations = [];

        if ('' === trim($definition->toolSlug()) || 1 !== preg_match('/^[A-Z][A-Za-z0-9]*$/', $definition->toolSlug())) {
            $violations[] = $this->violation('error', $definition, 'toolSlug', 'Tool slug must be non-empty PascalCase.', 'PascalCase', $definition->toolSlug());
        }

        if ('' === trim($definition->serviceClass) || !str_ends_with($definition->serviceClass, '\\'.$definition->serviceShortName())) {
            $violations[] = $this->violation('error', $definition, 'serviceClass', 'Service class must end with the declared service short nameEntity.', '*\\'.$definition->serviceShortName(), $definition->serviceClass);
        }

        if (!$definition->isOwnerSidePrefixed()) {
            $violations[] = $this->violation('error', $definition, 'serviceShortName', 'Producer tool service must use producer-side Configuration prefix and Service suffix.', $definition->expectedServicePrefix().'*Service', $definition->serviceShortName());
        }

        return $violations;
    }

    /** @return list<AdministrationOwnerConfigurationToolViolation> */
    private function formConventionViolations(AdministrationConfigurationToolDefinition $definition): array
    {
        $violations = [];
        $expectedFormSuffix = $definition->expectedServicePrefix().$definition->toolSlug().'FormType';

        if (null !== $definition->formTypeClass && !str_ends_with($definition->formTypeClass, '\\'.$expectedFormSuffix)) {
            $violations[] = $this->violation('warning', $definition, 'formTypeClass', 'Producer form type should follow producer-side Configuration prefix convention.', '*\\'.$expectedFormSuffix, $definition->formTypeClass);
        }

        $expectedDataSuffix = $definition->expectedServicePrefix().$definition->toolSlug().'Data';
        if (null !== $definition->formDataClass && !str_ends_with($definition->formDataClass, '\\'.$expectedDataSuffix)) {
            $violations[] = $this->violation('warning', $definition, 'formDataClass', 'Producer form data should follow producer-side Configuration prefix convention.', '*\\'.$expectedDataSuffix, $definition->formDataClass);
        }

        return $violations;
    }

    /** @return list<AdministrationOwnerConfigurationToolViolation> */
    private function executableContractViolations(AdministrationConfigurationToolDefinition $definition): array
    {
        $violations = [];
        $variableDriven = is_a($definition->serviceClass, AdministrationConfigVariableToolServiceInterface::class, true);

        if ($definition->executable && null === $definition->formTypeClass && !$variableDriven) {
            $violations[] = $this->violation('error', $definition, 'formTypeClass', 'Executable producer tool must either expose a legacy form type or implement the Configuring variable-driven tool contract.');
        }

        if ($definition->executable && null === $definition->formDataClass && !$variableDriven) {
            $violations[] = $this->violation('warning', $definition, 'formDataClass', 'Legacy executable producer tool should expose a form data class for stable form payload semantics.');
        }

        return $violations;
    }

    /** @return list<AdministrationOwnerConfigurationToolViolation> */
    private function toolKeyViolations(AdministrationConfigurationToolDefinition $definition): array
    {
        $toolKey = $definition->toolKey();
        $expectedToolKey = strtolower($definition->componentToken()).'.'.$this->camelToSnake($definition->toolSlug());

        if ($toolKey !== $expectedToolKey) {
            return [$this->violation('error', $definition, 'toolKey', 'Tool key must be derived from component token and tool slug.', $expectedToolKey, $toolKey)];
        }

        return [];
    }

    private function violation(
        string $severity,
        AdministrationConfigurationToolDefinition $definition,
        string $field,
        string $message,
        ?string $expected = null,
        ?string $actual = null,
    ): AdministrationOwnerConfigurationToolViolation {
        return new AdministrationOwnerConfigurationToolViolation(
            severity: $severity,
            componentKey: $definition->componentKey(),
            componentToken: $definition->componentToken(),
            toolKey: $definition->toolKey(),
            field: $field,
            message: $message,
            expected: $expected,
            actual: $actual,
        );
    }

    private function camelToSnake(string $value): string
    {
        $snake = (string) preg_replace('/(?<!^)[A-Z]/', '_$0', trim($value));

        return strtolower($snake);
    }
}
