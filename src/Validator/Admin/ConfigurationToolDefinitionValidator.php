<?php

declare(strict_types=1);

namespace App\Administering\Validator\Admin;

use App\Administering\ValidatorInterface\Admin\ConfigurationToolDefinitionValidatorInterface;
use App\Administering\Value\Admin\AdministrationOwnerConfigurationToolViolation;
use App\Configuring\ServiceInterface\Config\ConfigVariableToolServiceInterface;
use App\Configuring\ServiceInterface\Tool\ConfigurationToolProviderInterface;
use App\Configuring\Value\Tool\ConfigurationToolDefinition;

/**
 * Validates producer-side configuration tool definitions before materialization.
 *
 * This keeps Administering as a thin projection/orchestration shell while still
 * rejecting producer definitions that would create unstable SQLite/EasyAdmin rows.
 */
final readonly class ConfigurationToolDefinitionValidator implements ConfigurationToolDefinitionValidatorInterface
{
    public function validate(
        ConfigurationToolProviderInterface $provider,
        ConfigurationToolDefinition $definition,
    ): array {
        $violations = [];
        $componentKey = $definition->componentKey;
        $componentToken = $definition->componentToken;
        $toolKey = $definition->toolKey();

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

        if ('' === trim($definition->toolSlug) || 1 !== preg_match('/^[A-Z][A-Za-z0-9]*$/', $definition->toolSlug)) {
            $violations[] = $this->violation('error', $definition, 'toolSlug', 'Tool slug must be non-empty PascalCase.', 'PascalCase', $definition->toolSlug);
        }

        if ('' === trim($definition->serviceClass) || !str_ends_with($definition->serviceClass, '\\'.$definition->serviceShortName)) {
            $violations[] = $this->violation('error', $definition, 'serviceClass', 'Service class must end with the declared service short name.', '*\\'.$definition->serviceShortName, $definition->serviceClass);
        }

        if (!$definition->isProducerSidePrefixed()) {
            $violations[] = $this->violation('error', $definition, 'serviceShortName', 'Producer tool service must use producer-side Configuration prefix and Service suffix.', $definition->expectedServicePrefix().'*Service', $definition->serviceShortName);
        }

        $expectedFormSuffix = $definition->expectedServicePrefix().$definition->toolSlug.'FormType';
        if (null !== $definition->formTypeClass && !str_ends_with($definition->formTypeClass, '\\'.$expectedFormSuffix)) {
            $violations[] = $this->violation('warning', $definition, 'formTypeClass', 'Producer form type should follow producer-side Configuration prefix convention.', '*\\'.$expectedFormSuffix, $definition->formTypeClass);
        }

        $expectedDataSuffix = $definition->expectedServicePrefix().$definition->toolSlug.'Data';
        if (null !== $definition->formDataClass && !str_ends_with($definition->formDataClass, '\\'.$expectedDataSuffix)) {
            $violations[] = $this->violation('warning', $definition, 'formDataClass', 'Producer form data should follow producer-side Configuration prefix convention.', '*\\'.$expectedDataSuffix, $definition->formDataClass);
        }

        $variableDriven = is_a($definition->serviceClass, ConfigVariableToolServiceInterface::class, true);
        if ($definition->executable && null === $definition->formTypeClass && !$variableDriven) {
            $violations[] = $this->violation('error', $definition, 'formTypeClass', 'Executable producer tool must either expose a legacy form type or implement the Configuring variable-driven tool contract.');
        }

        if ($definition->executable && null === $definition->formDataClass && !$variableDriven) {
            $violations[] = $this->violation('warning', $definition, 'formDataClass', 'Legacy executable producer tool should expose a form data class for stable form payload semantics.');
        }

        if ($toolKey !== strtolower($componentToken).'.'.$this->camelToSnake($definition->toolSlug)) {
            $violations[] = $this->violation('error', $definition, 'toolKey', 'Tool key must be derived from component token and tool slug.', strtolower($componentToken).'.'.$this->camelToSnake($definition->toolSlug), $toolKey);
        }

        return $violations;
    }

    private function violation(
        string $severity,
        ConfigurationToolDefinition $definition,
        string $field,
        string $message,
        ?string $expected = null,
        ?string $actual = null,
    ): AdministrationOwnerConfigurationToolViolation {
        return new AdministrationOwnerConfigurationToolViolation(
            severity: $severity,
            componentKey: $definition->componentKey,
            componentToken: $definition->componentToken,
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
