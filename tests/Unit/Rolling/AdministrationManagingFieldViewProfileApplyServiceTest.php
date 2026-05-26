<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Rolling;

use App\Administering\Service\Managing\AdministrationManagingFieldViewProfileApplyService;
use App\Managing\Service\Administration\ManagingFieldViewProfileApplyService;
use App\Managing\Value\Administration\ManagingFieldViewProfileApplyRequest;
use PHPUnit\Framework\TestCase;

final class AdministrationManagingFieldViewProfileApplyServiceTest extends TestCase
{
    public function testPreparesManagingApplyPayload(): void
    {
        $service = new AdministrationManagingFieldViewProfileApplyService(new ManagingFieldViewProfileApplyService());
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

        self::assertTrue($result->accepted);
        self::assertSame('field_view_profile_apply_payload_prepared', $result->reason);
        self::assertSame('administering:operator', $result->managingApplyPayload['actor_identifier']);
        self::assertSame('App\\Managing\\HandlerInterface\\Crud\\ManageCrudFieldUserProfileApplyHandlerInterface', $result->toSafeArray()['managing_apply_contract']);
        self::assertFalse($result->toSafeArray()['safety']['grants_access']);
    }

    public function testRejectsUntrustedSurface(): void
    {
        $service = new AdministrationManagingFieldViewProfileApplyService(new ManagingFieldViewProfileApplyService());
        $result = $service->prepare(new ManagingFieldViewProfileApplyRequest(
            normalizedProfilePayload: ['subjects' => ['user:42' => ['defaults' => ['index' => ['hidden' => ['createdAt']]]]]],
            reviewContext: [
                'surface' => 'random_surface',
                'subject_key' => 'user:42',
                'profile_permission' => 'managing.field.profile.user_update',
                'page_name' => 'index',
            ],
        ));

        self::assertFalse($result->accepted);
        self::assertSame('field_view_profile_apply_untrusted_surface', $result->reason);
    }
}
