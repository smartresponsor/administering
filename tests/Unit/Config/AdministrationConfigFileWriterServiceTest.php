<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Config;

use App\Administering\Service\Config\AdministrationConfigFileWriterService;
use PHPUnit\Framework\TestCase;

final class AdministrationConfigFileWriterServiceTest extends TestCase
{
    public function testRejectsNonWhitelistedPath(): void
    {
        $service = new AdministrationConfigFileWriterService(sys_get_temp_dir());
        $result = $service->write(sys_get_temp_dir(), 'secrets.yaml', ['foo' => 'bar'], ['config/component/component.yaml']);

        self::assertSame('failed', $result['status']);
        self::assertStringContainsString('whitelisted', $result['message']);
    }
}
