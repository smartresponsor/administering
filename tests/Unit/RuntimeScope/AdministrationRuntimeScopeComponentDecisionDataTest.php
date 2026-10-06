<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\RuntimeScope;

use App\Administering\Entity\AdministrationConnectedComponentRecordEntity;
use App\Administering\Value\Form\RuntimeScope\AdministrationRuntimeScopeComponentDecisionData;
use PHPUnit\Framework\TestCase;

final class AdministrationRuntimeScopeComponentDecisionDataTest extends TestCase
{
    public function testItReadsDevDecisionFromConnectedComponentRecord(): void
    {
        $record = new AdministrationConnectedComponentRecordEntity('accessing', 'present', 'ready', [
            'metadata' => [
                'dev' => [
                    'enabled' => true,
                    'status' => 'enabled',
                ],
                'prod' => [
                    'enabled' => false,
                    'status' => 'disabled',
                ],
            ],
        ]);

        $data = AdministrationRuntimeScopeComponentDecisionData::fromRecord($record, 'dev');

        self::assertSame('accessing', $data->componentKey);
        self::assertSame('dev', $data->environment);
        self::assertTrue($data->enabled);
        self::assertNull($data->reason);
    }

    public function testItReadsProdDecisionFromConnectedComponentRecord(): void
    {
        $record = new AdministrationConnectedComponentRecordEntity('paying', 'present', 'pending', [
            'metadata' => [
                'dev' => [
                    'enabled' => true,
                    'status' => 'enabled',
                ],
                'prod' => [
                    'enabled' => false,
                    'status' => 'disabled',
                ],
            ],
        ]);

        $data = AdministrationRuntimeScopeComponentDecisionData::fromRecord($record, 'prod');

        self::assertSame('paying', $data->componentKey);
        self::assertSame('prod', $data->environment);
        self::assertFalse($data->enabled);
    }
}
