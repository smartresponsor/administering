<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Config;

use App\Administering\Service\Config\ConfigSecretService;
use App\Administering\ServiceInterface\Credential\AdministrationCredentialOperatorInterface;
use App\Administering\Value\Credential\AdministrationCredentialOperationResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ConfigSecretServiceTest extends TestCase
{
    public function testRejectsUnknownSecretField(): void
    {
        $operator = $this->createMock(AdministrationCredentialOperatorInterface::class);
        $service = new ConfigSecretService($operator);

        $result = $service->replace('prod', ['unknownField' => 'secret'], ['knownField' => 'APP_SECRET']);

        self::assertSame('failed', $result['status']);
        self::assertSame([], $result['masked_changes']);
    }

    public function testReplacesAllowedSecretField(): void
    {
        /** @var AdministrationCredentialOperatorInterface&MockObject $operator */
        $operator = $this->createMock(AdministrationCredentialOperatorInterface::class);
        $operator->expects(self::once())
            ->method('set')
            ->with('prod', 'APP_SECRET', 'new-secret')
            ->willReturn(new AdministrationCredentialOperationResult(true, 'set', 'APP_SECRET', 'prod', ['ok']));

        $service = new ConfigSecretService($operator);
        $result = $service->replace('prod', ['appSecretReplacement' => 'new-secret'], ['appSecretReplacement' => 'APP_SECRET']);

        self::assertSame('applied', $result['status']);
        self::assertSame('********', $result['masked_changes']['appSecretReplacement']);
    }
}
