<?php

declare(strict_types=1);

namespace App\Administering\Tests\Validator\Admin;

use App\Administering\ServiceInterface\Admin\AdministrationOwnerConfigurationToolProviderInterface;
use App\Administering\Validator\Admin\AdministrationOwnerConfigurationToolDefinitionValidator;
use App\Administering\Value\Admin\AdministrationOwnerConfigurationToolDefinition;
use PHPUnit\Framework\TestCase;

final class AdministrationOwnerConfigurationToolDefinitionValidatorTest extends TestCase
{
    public function testCanonicalExecutableDefinitionHasNoViolations(): void
    {
        $validator = new AdministrationOwnerConfigurationToolDefinitionValidator();

        $violations = $validator->validate(
            $this->provider('Managing', 'managing'),
            new AdministrationOwnerConfigurationToolDefinition(
                componentKey: 'Managing',
                componentToken: 'managing',
                toolSlug: 'FieldAccess',
                serviceClass: 'App\\Managing\\Service\\ManagingConfigurationFieldAccessService',
                serviceShortName: 'ManagingConfigurationFieldAccessService',
                label: 'Field access',
                formTypeClass: 'App\\Managing\\Form\\ManagingConfigurationFieldAccessFormType',
                formDataClass: 'App\\Managing\\Value\\ManagingConfigurationFieldAccessData',
                executable: true,
            ),
        );

        self::assertSame([], $violations);
    }

    public function testMalformedAndMismatchedDefinitionReportsDeterministicFields(): void
    {
        $validator = new AdministrationOwnerConfigurationToolDefinitionValidator();

        $violations = $validator->validate(
            $this->provider('Managing', 'managing'),
            new AdministrationOwnerConfigurationToolDefinition(
                componentKey: '',
                componentToken: 'Bad-Token',
                toolSlug: 'bad_slug',
                serviceClass: 'App\\Broken\\UnexpectedService',
                serviceShortName: 'Mismatch',
                label: 'Broken',
                formTypeClass: 'App\\Broken\\BadType',
                formDataClass: 'App\\Broken\\BadData',
            ),
        );

        self::assertSame(
            [
                'componentKey',
                'componentToken',
                'componentKey',
                'componentToken',
                'toolSlug',
                'serviceClass',
                'serviceShortName',
                'formTypeClass',
                'formDataClass',
            ],
            array_map(static fn ($violation): string => $violation->field, $violations),
        );
        self::assertSame(
            ['error', 'error', 'error', 'error', 'error', 'error', 'error', 'warning', 'warning'],
            array_map(static fn ($violation): string => $violation->severity, $violations),
        );
        self::assertSame('Component key must not be blank.', $violations[0]->message);
        self::assertSame('lowercase snake token', $violations[1]->expected);
        self::assertSame('Bad-Token', $violations[1]->actual);
    }

    public function testExecutableDefinitionWithoutFormContractsFailsClosed(): void
    {
        $validator = new AdministrationOwnerConfigurationToolDefinitionValidator();

        $violations = $validator->validate(
            $this->provider('Managing', 'managing'),
            new AdministrationOwnerConfigurationToolDefinition(
                componentKey: 'Managing',
                componentToken: 'managing',
                toolSlug: 'FieldAccess',
                serviceClass: 'App\\Managing\\Service\\ManagingConfigurationFieldAccessService',
                serviceShortName: 'ManagingConfigurationFieldAccessService',
                label: 'Field access',
                executable: true,
            ),
        );

        self::assertCount(2, $violations);
        self::assertSame('formTypeClass', $violations[0]->field);
        self::assertSame('error', $violations[0]->severity);
        self::assertSame('formDataClass', $violations[1]->field);
        self::assertSame('warning', $violations[1]->severity);
    }

    private function provider(string $componentKey, string $componentToken): AdministrationOwnerConfigurationToolProviderInterface
    {
        return new class($componentKey, $componentToken) implements AdministrationOwnerConfigurationToolProviderInterface {
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
