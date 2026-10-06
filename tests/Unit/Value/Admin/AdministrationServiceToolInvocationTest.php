<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Value\Admin;

use App\Administering\Value\Admin\AdministrationServiceToolInvocation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AdministrationServiceToolInvocationTest extends TestCase
{
    public function testFromSafeContextBuildsNormalizedInvocation(): void
    {
        $context = [
            'toolKey' => 'configuration.cache',
            'sectionKey' => 'configuration',
            'toolSlug' => 'cache',
            'serviceClass' => 'App\\Tool\\CacheTool',
            'serviceFile' => 'src/Tool/CacheTool.php',
            'formTypeClass' => 'App\\Form\\CacheType',
            'formDataClass' => 'App\\DTO\\CacheData',
            'executable' => 'true',
            'sourceOwnership' => 'owner_component',
            'ownerComponentKey' => 'configuring',
            'ownerComponentToken' => 'Configuring',
            'ownerProviderClass' => 'App\\Provider\\ConfiguringTools',
            'ownerServiceClass' => 'App\\Tool\\CacheTool',
            'ownerSourceLabel' => 'Configuring provider',
            'formData' => [
                'mode' => '  warm  ',
                'targets' => [' one ', '', 'two'],
                '_data_class' => ' App\\DTO\\CacheData ',
            ],
        ];

        $invocation = AdministrationServiceToolInvocation::fromSafeContext('operation-1', $context);

        self::assertSame('operation-1', $invocation->operationKey);
        self::assertSame('configuration.cache', $invocation->toolKey);
        self::assertSame('configuration', $invocation->sectionKey);
        self::assertSame('cache', $invocation->toolSlug);
        self::assertSame('App\\Tool\\CacheTool', $invocation->serviceClass);
        self::assertSame('src/Tool/CacheTool.php', $invocation->serviceFile);
        self::assertSame('App\\Form\\CacheType', $invocation->formTypeClass);
        self::assertSame('App\\DTO\\CacheData', $invocation->formDataClass);
        self::assertTrue($invocation->executable);
        self::assertSame('owner_component', $invocation->sourceOwnership);
        self::assertSame('configuring', $invocation->ownerComponentKey);
        self::assertSame('Configuring', $invocation->ownerComponentToken);
        self::assertSame('App\\Provider\\ConfiguringTools', $invocation->ownerProviderClass);
        self::assertSame('App\\Tool\\CacheTool', $invocation->ownerServiceClass);
        self::assertSame('Configuring provider', $invocation->ownerSourceLabel);
        self::assertSame($context, $invocation->safeContext);
        self::assertSame('warm', $invocation->stringFormValue('mode'));
        self::assertSame(['one', 'two'], $invocation->stringListFormValue('targets'));
        self::assertSame(' App\\DTO\\CacheData ', $invocation->formDataClass());
    }

    public function testFromSafeContextUsesSafeDefaultsForOptionalValues(): void
    {
        $invocation = AdministrationServiceToolInvocation::fromSafeContext('operation-2', [
            'toolKey' => 'tool',
            'sectionKey' => 'section',
            'toolSlug' => 'slug',
            'serviceClass' => 'App\\Tool',
            'formData' => 'not-an-array',
        ]);

        self::assertFalse($invocation->executable);
        self::assertSame('administering_internal', $invocation->sourceOwnership);
        self::assertNull($invocation->serviceFile);
        self::assertNull($invocation->formTypeClass);
        self::assertNull($invocation->formDataClass);
        self::assertNull($invocation->ownerComponentKey);
        self::assertNull($invocation->ownerComponentToken);
        self::assertNull($invocation->ownerProviderClass);
        self::assertNull($invocation->ownerServiceClass);
        self::assertNull($invocation->ownerSourceLabel);
        self::assertSame([], $invocation->formData);
    }

    #[DataProvider('truthyExecutableValues')]
    public function testExecutableAcceptsCanonicalTruthyValues(mixed $value): void
    {
        $invocation = AdministrationServiceToolInvocation::fromSafeContext('operation-3', [
            'toolKey' => 'tool',
            'sectionKey' => 'section',
            'toolSlug' => 'slug',
            'serviceClass' => 'App\\Tool',
            'executable' => $value,
        ]);

        self::assertTrue($invocation->executable);
    }

    /** @return iterable<string, array{mixed}> */
    public static function truthyExecutableValues(): iterable
    {
        yield 'boolean true' => [true];
        yield 'integer one' => [1];
        yield 'string one' => ['1'];
        yield 'string true' => ['true'];
    }

    public function testMissingRequiredStringFailsClosed(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Service tool invocation context is missing required string "toolKey".');

        AdministrationServiceToolInvocation::fromSafeContext('operation-4', [
            'sectionKey' => 'section',
            'toolSlug' => 'slug',
            'serviceClass' => 'App\\Tool',
        ]);
    }

    public function testFormValueHelpersRejectNonScalarAndNonArrayValues(): void
    {
        $invocation = $this->invocation([
            'object' => new \stdClass(),
            'list' => 'not-a-list',
            '_data_class' => ' ',
        ]);

        self::assertSame('fallback', $invocation->stringFormValue('object', 'fallback'));
        self::assertSame('fallback', $invocation->stringFormValue('missing', 'fallback'));
        self::assertSame([], $invocation->stringListFormValue('list'));
        self::assertSame([], $invocation->stringListFormValue('missing'));
        self::assertNull($invocation->formDataClass());
    }

    public function testStringListHelperNormalizesScalarItems(): void
    {
        $invocation = $this->invocation([
            'targets' => [' first ', 2, true, null, ''],
        ]);

        self::assertSame(['first', '2', '1'], $invocation->stringListFormValue('targets'));
    }

    /** @param array<string, mixed> $formData */
    private function invocation(array $formData): AdministrationServiceToolInvocation
    {
        return new AdministrationServiceToolInvocation(
            operationKey: 'operation',
            toolKey: 'tool',
            sectionKey: 'section',
            toolSlug: 'slug',
            serviceClass: 'App\\Tool',
            serviceFile: null,
            formTypeClass: null,
            formDataClass: null,
            executable: false,
            sourceOwnership: 'administering_internal',
            ownerComponentKey: null,
            ownerComponentToken: null,
            ownerProviderClass: null,
            ownerServiceClass: null,
            ownerSourceLabel: null,
            formData: $formData,
            safeContext: [],
        );
    }
}
