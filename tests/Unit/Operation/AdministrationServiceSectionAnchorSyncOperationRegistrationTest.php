<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Operation;

use App\Administering\Value\Operation\AdministrationOperationType;
use PHPUnit\Framework\TestCase;

final class AdministrationServiceSectionAnchorSyncOperationRegistrationTest extends TestCase
{
    public function testServiceSectionAnchorSyncIsLaunchableOperation(): void
    {
        self::assertTrue(AdministrationOperationType::isKnown(AdministrationOperationType::SERVICE_SECTION_ANCHORS_SYNC));
        self::assertTrue(AdministrationOperationType::isLaunchable(AdministrationOperationType::SERVICE_SECTION_ANCHORS_SYNC));
        self::assertContains(AdministrationOperationType::SERVICE_SECTION_ANCHORS_SYNC, AdministrationOperationType::launchable());
    }
}
