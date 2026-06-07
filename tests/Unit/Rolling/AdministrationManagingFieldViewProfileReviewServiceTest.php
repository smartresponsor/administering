<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Rolling;

use App\Administering\Service\Managing\AdministrationManagingFieldViewProfileReviewService;
use App\Administering\Value\Managing\ManagingFieldViewProfileEditRequest;
use PHPUnit\Framework\TestCase;

final class AdministrationManagingFieldViewProfileReviewServiceTest extends TestCase
{
    public function testBuildsResourceProfileReviewPayload(): void
    {
        $service = new AdministrationManagingFieldViewProfileReviewService();
        $requestedPayload = [
            'subjects' => [
                'role:security.admin' => [
                    'resources' => [
                        'App\\Cataloging\\Entity\\Catalog\\CatalogCategoryEntity' => [
                            'index' => [
                                'visible' => ['title', 'status'],
                                'hidden' => ['createdAt'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = $service->review(new ManagingFieldViewProfileEditRequest(
            profileKey: 'role:security.admin',
            currentProfilePayload: [],
            requestedProfilePayload: $requestedPayload,
            requestedBySubject: 'administering:operator',
            reason: 'Owner approved table cleanup.',
        ));

        self::assertSame('profile_payload_update', $result->changeType);
        self::assertSame($requestedPayload, $result->normalizedProfilePayload);
        self::assertSame('administering:operator', $result->reviewContext['requested_by_subject']);
        self::assertFalse($result->toSafeArray()['safety']['grants_access']);
    }

    public function testBuildsNoChangeReviewPayload(): void
    {
        $service = new AdministrationManagingFieldViewProfileReviewService();
        $payload = ['subjects' => ['user:42' => ['defaults' => ['detail' => ['visible' => ['description']]]]]];
        $result = $service->review(new ManagingFieldViewProfileEditRequest(
            profileKey: 'user:42',
            currentProfilePayload: $payload,
            requestedProfilePayload: $payload,
            requestedBySubject: 'administering:operator',
        ));

        self::assertSame('no_change', $result->changeType);
        self::assertSame($payload, $result->normalizedProfilePayload);
    }

    public function testRejectsMissingProfileKey(): void
    {
        $result = (new AdministrationManagingFieldViewProfileReviewService())->review(new ManagingFieldViewProfileEditRequest(
            profileKey: '',
            currentProfilePayload: [],
            requestedProfilePayload: ['subjects' => []],
            requestedBySubject: 'administering:operator',
        ));

        self::assertFalse($result->valid);
        self::assertContains('Missing profile key.', $result->violations);
    }
}
