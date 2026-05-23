<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Admin;

use App\Administering\Value\Admin\AdministrationServiceSectionAnchorSyncResult;
use PHPUnit\Framework\TestCase;

final class AdministrationServiceSectionAnchorSyncResultTest extends TestCase
{
    public function testItExposesAStableSafeArrayShape(): void
    {
        $result = new AdministrationServiceSectionAnchorSyncResult('Symfony', 12, 'synced', ['routes refreshed']);

        self::assertSame([
            'sectionKey' => 'Symfony',
            'recordCount' => 12,
            'status' => 'synced',
            'messages' => ['routes refreshed'],
        ], $result->toArray());
    }
}
