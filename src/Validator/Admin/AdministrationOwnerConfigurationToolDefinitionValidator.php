<?php

declare(strict_types=1);

namespace App\Administering\Validator\Admin;

use App\Administering\ServiceInterface\Admin\AdministrationOwnerConfigurationToolProviderInterface;
use App\Administering\ValidatorInterface\Admin\AdministrationOwnerConfigurationToolDefinitionValidatorInterface;
use App\Administering\Value\Admin\AdministrationOwnerConfigurationToolDefinition;
use App\Administering\Value\Admin\AdministrationOwnerConfigurationToolViolation;

/**
 * Validates owner-side configuration tool definitions before materialization.
 *
 * This keeps Administering as a thin projection/orchestration shell while still
 * rejecting owner definitions that would create unstable SQLite/EasyAdmin rows.
 */
final readonly class AdministrationOwnerConfigurationToolDefinitionValidator implements AdministrationOwnerConfigurationToolDefinitionValidatorInterface
{
    public function validate(
        AdministrationOwnerConfigurationToolProviderInterface $provider,
        AdministrationOwnerConfigurationToolDefinition $definition,
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

        if (!$definition->isOwnerSidePrefixed()) {
            $violations[] = $this->violation('error', $definition, 'serviceShortName', 'Owner tool service must use owner-side Configuration prefix and Service suffix.', $definition->expectedServicePrefix().'*Service', $definition->serviceShortName);
        }

        $expectedFormSuffix = $definition->expectedServicePrefix().$definition->toolSlug.'FormType';
        if (null !== $definition->formTypeClass && !str_ends_with($definition->formTypeClass, '\\'.$expectedFormSuffix)) {
            $violations[] = $this->violation('warning', $definition, 'formTypeClass', 'Owner form type should follow owner-side Configuration prefix convention.', '*\\'.$expectedFormSuffix, $definition->formTypeClass);
        }

        $expectedDataSuffix = $definition->expectedServicePrefix().$definition->toolSlug.'Data';
        if (null !== $definition->formDataClass && !str_ends_with($definition->formDataClass, '\\'.$expectedDataSuffix)) {
            $violations[] = $this->violation('warning', $definition, 'formDataClass', 'Owner form data should follow owner-side Configuration prefix convention.', '*\\'.$expectedDataSuffix, $definition->formDataClass);
        }

        if ($definition->executable && null === $definition->formTypeClass) {
            $violations[] = $this->violation('error', $definition, 'formTypeClass', 'Executable owner tool must expose a form type so Administering can open it safely.');
        }

        if ($definition->executable && null === $definition->formDataClass) {
            $violations[] = $this->violation('warning', $definition, 'formDataClass', 'Executable owner tool should expose a form data class for stable form payload semantics.');
        }

        if ($toolKey !== strtolower($componentToken).'.'.$this->camelToSnake($definition->toolSlug)) {
            $violations[] = $this->violation('error', $definition, 'toolKey', 'Tool key must be derived from component token and tool slug.', strtolower($componentToken).'.'.$this->camelToSnake($definition->toolSlug), $toolKey);
        }

        return $violations;
    }

    private function violation(
        string $severity,
        AdministrationOwnerConfigurationToolDefinition $definition,
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
