<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Rolling;

use App\Administering\Service\Managing\AdministrationManagingFieldAccessMutationApplyService;
use App\Administering\ServiceInterface\Accessing\AdministrationCurrentUserContextProviderInterface;
use App\Administering\Value\AdministrationCurrentUserContext;
use PHPUnit\Framework\TestCase;

final class AdministrationManagingFieldAccessMutationApplyServiceTest extends TestCase
{
    public function testStandaloneRuntimeSkipsOwnerSideApply(): void
    {
        $currentUserProvider = new class implements AdministrationCurrentUserContextProviderInterface {
            public function current(): AdministrationCurrentUserContext
            {
                return new AdministrationCurrentUserContext('administering:operator', 'user:op');
            }
        };

        $service = new AdministrationManagingFieldAccessMutationApplyService($currentUserProvider);
        $result = $service->applyReviewedFieldAccessMutation('review-key', 'administering:operator');

        self::assertFalse($result->succeeded());
        self::assertSame('skipped', $result->status());
        self::assertSame('review-key', $result->requestKey());
        self::assertSame('administering_self_contained_dry_runtime', $result->safeContext()['mode']);
    }
}
