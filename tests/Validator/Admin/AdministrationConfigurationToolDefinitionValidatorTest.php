<?php

declare(strict_types=1);

namespace App\Administering\Tests\Validator\Admin;

use App\Administering\ServiceInterface\Tool\AdministrationConfigurationToolProviderInterface;
use App\Administering\Validator\Admin\AdministrationConfigurationToolDefinitionValidator;
use App\Administering\Value\Tool\AdministrationConfigurationToolDefinition;
use PHPUnit\Framework\TestCase;

final class AdministrationConfigurationToolDefinitionValidatorTest extends TestCase
{
    public function testCanonicalDefinitionHasNoViolations(): void
    {
        $validator = new AdministrationConfigurationToolDefinitionValidator();

        $violations = $validator->validate(
            $this->provider('Administering', 'administering'),
            new AdministrationConfigurationToolDefinition(
                key: 'Cache',
                label: 'Cache',
                description: 'Configure cache behavior.',
                serviceClass: 'App\\Administering\\Service\\Config\\AdministeringConfigurationCacheService',
                operation: 'configure',
                componentKey: 'Administering',
                componentToken: 'administering',
                toolSlug: 'Cache',
                serviceShortName: 'AdministeringConfigurationCacheService',
            ),
        );

        self::assertSame([], $violations);
    }

    public function testMalformedAndMismatchedDefinitionReportsDeterministicFields(): void
    {
        $validator = new AdministrationConfigurationToolDefinitionValidator();

        $violations = $validator->validate(
            $this->provider('Administering', 'administering'),
            new AdministrationConfigurationToolDefinition(
                key: 'broken',
                label: 'Broken',
                description: 'Invalid producer definition.',
                serviceClass: 'App\\Broken\\Service\\UnexpectedService',
                operation: 'configure',
                componentKey: 'WrongComponent',
                componentToken: 'Bad-Token',
                toolSlug: 'bad_slug',
                serviceShortName: 'Mismatch',
                formTypeClass: 'App\\Broken\\Form\\BadType',
                formDataClass: 'App\\Broken\\Value\\BadData',
            ),
        );

        $fields = array_map(static fn ($violation): string => $violation->field, $violations);
        $severities = array_map(static fn ($violation): string => $violation->severity, $violations);

        self::assertSame([
            'componentToken',
            'componentKey',
            'componentToken',
            'toolSlug',
            'serviceClass',
            'serviceShortName',
            'formTypeClass',
            'formDataClass',
        ], $fields);
        self::assertSame([
            'error',
            'error',
            'error',
            'error',
            'error',
            'error',
            'warning',
            'warning',
        ], $severities);
        self::assertTrue($violations[0]->isError());
        self::assertSame('lowercase snake token', $violations[0]->expected);
        self::assertSame('Bad-Token', $violations[0]->actual);
    }

    public function testExecutableLegacyDefinitionRequiresFormContracts(): void
    {
        $validator = new AdministrationConfigurationToolDefinitionValidator();

        $violations = $validator->validate(
            $this->provider('Administering', 'administering'),
            new AdministrationConfigurationToolDefinition(
                key: 'Cache',
                label: 'Cache',
                description: 'Configure cache behavior.',
                serviceClass: 'App\\Administering\\Service\\Config\\AdministeringConfigurationCacheService',
                operation: 'configure',
                componentKey: 'Administering',
                componentToken: 'administering',
                toolSlug: 'Cache',
                serviceShortName: 'AdministeringConfigurationCacheService',
                executable: true,
            ),
        );

        self::assertCount(2, $violations);
        self::assertSame('formTypeClass', $violations[0]->field);
        self::assertSame('error', $violations[0]->severity);
        self::assertSame('formDataClass', $violations[1]->field);
        self::assertSame('warning', $violations[1]->severity);
    }

    private function provider(string $componentKey, string $componentToken): AdministrationConfigurationToolProviderInterface
    {
        return new class($componentKey, $componentToken) implements AdministrationConfigurationToolProviderInterface {
            public function __construct(
                private readonly string $componentKey,
                private readonly string $componentToken,
            ) {
            }

            public function componentKey(): string
            {
                return $this->componentKey;
            }

            public function componentToken(): string
            {
                return $this->componentToken;
            }

            public function tools(): iterable
            {
                return [];
            }
        };
    }
}
