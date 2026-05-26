<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Rolling;

use App\Administering\Service\Managing\AdministrationManagingFieldAccessMutationApplyService;
use App\Administering\ServiceInterface\Accessing\AdministrationCurrentUserContextProviderInterface;
use App\Administering\Value\AdministrationCurrentUserContext;
use App\Managing\ServiceInterface\Administration\ManagingFieldAccessMutationApplyServiceInterface;
use App\Managing\Value\Administration\ManagingFieldAccessMutationApplyResult;
use PHPUnit\Framework\TestCase;

final class AdministrationManagingFieldAccessMutationApplyServiceTest extends TestCase
{
    public function testDelegatesToOwnerSideApplyService(): void
    {
        $captured = [];
        $ownerService = new class($captured) implements ManagingFieldAccessMutationApplyServiceInterface {
            public function __construct(private array &$captured)
            {
            }

            public function applyReviewedFieldAccessMutation(string $requestKey, string $requestedBySubject): ManagingFieldAccessMutationApplyResult
            {
                $this->captured = [$requestKey, $requestedBySubject];

                return ManagingFieldAccessMutationApplyResult::fromRollingResult(
                    $requestKey,
                    true,
                    'succeeded',
                    'ok',
                    ['delegated' => true],
                );
            }
        };

        $currentUserProvider = new class implements AdministrationCurrentUserContextProviderInterface {
            public function current(): ?AdministrationCurrentUserContext
            {
                return new AdministrationCurrentUserContext('administering:operator', 'user:op');
            }
        };

        $service = new AdministrationManagingFieldAccessMutationApplyService($currentUserProvider, $ownerService);
        $result = $service->applyReviewedFieldAccessMutation('review-key', 'administering:operator');

        self::assertTrue($result->succeeded());
        self::assertSame(['review-key', 'administering:operator'], $captured);
    }
}
