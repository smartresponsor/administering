<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Rolling;

use App\Administering\Service\Managing\AdministrationManagingFieldViewProfileApplyService;
use App\Administering\Value\Managing\ManagingFieldViewProfileApplyRequest;
use PHPUnit\Framework\TestCase;

final class AdministrationManagingFieldViewProfileApplyServiceTest extends TestCase
{
    public function testPreparesManagingApplyPayload(): void
    {
        $service = new AdministrationManagingFieldViewProfileApplyService();
        $result = $service->prepare(new ManagingFieldViewProfileApplyRequest(
            normalizedProfilePayload: [
                'subjects' => [
                    'user:42' => [
                        'defaults' => [
                            'index' => [
                                'hidden' => ['createdAt'],
                            ],
                        ],
                    ],
                ],
            ],
            reviewContext: [
                'surface' => 'managing_field_view_profile_review',
                'subject_key' => 'user:42',
                'profile_permission' => 'managing.field.profile.user_update',
                'mode' => 'replace',
                'page_name' => 'index',
            ],
            requestedBySubject: 'administering:operator',
        ));

        self::assertTrue($result->valid);
        self::assertSame('prepared', $result->status);
        self::assertSame('administering:operator', $result->safeContext['requested_by_subject']);
        self::assertSame('administering_self_contained_dry_runtime', $result->safeContext['mode']);
    }

    public function testRejectsEmptyPayload(): void
    {
        $service = new AdministrationManagingFieldViewProfileApplyService();
        $result = $service->prepare(new ManagingFieldViewProfileApplyRequest(
            normalizedProfilePayload: [],
            reviewContext: [
                'surface' => 'managing_field_view_profile_review',
                'subject_key' => 'user:42',
                'profile_permission' => 'managing.field.profile.user_update',
                'page_name' => 'index',
            ],
            requestedBySubject: 'administering:operator',
        ));

        self::assertFalse($result->valid);
        self::assertSame('rejected', $result->status);
    }
}
