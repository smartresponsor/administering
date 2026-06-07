<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Rolling;

use App\Administering\Entity\AdministrationAclMutationReviewRecord;
use App\Administering\Service\Managing\AdministrationManagingFieldAccessMutationReviewService;
use App\Administering\ServiceInterface\Rolling\AdministrationAclMutationReviewRecorderInterface;
use App\Administering\Value\Managing\ManagingFieldAccessMutationReviewInput;
use App\Administering\Value\Managing\ManagingFieldAccessPolicyDescriptor;
use App\Administering\Value\Managing\ManagingFieldAccessTarget;
use App\Administering\Value\Managing\ManagingFieldPermissionVocabulary;
use App\Administering\Value\Rolling\AdministrationRollingAclMutationRequest;
use App\Administering\Value\Rolling\AdministrationRollingAclMutationReview;
use PHPUnit\Framework\TestCase;

final class AdministrationManagingFieldAccessMutationReviewServiceTest extends TestCase
{
    public function testRoleAllowBuildsPermissionGrantReview(): void
    {
        $capture = new \stdClass();
        $service = $this->service($capture);
        $result = $service->review(new ManagingFieldAccessMutationReviewInput(
            new ManagingFieldAccessPolicyDescriptor(
                new ManagingFieldAccessTarget(
                    'Managing',
                    'App\\Cataloging\\Entity\\Catalog\\CatalogCategoryEntity',
                    'internalCost',
                    'detail',
                ),
                ManagingFieldPermissionVocabulary::FIELD_VIEW,
                ManagingFieldAccessPolicyDescriptor::SUBJECT_ROLE,
                'security.admin',
                'allow',
            ),
            'administering:operator',
        ));

        self::assertSame('review-key', $result->requestKey);
        self::assertSame('permission.grant', $capture->mutationType);
        self::assertSame('security.admin', $capture->subjectIdentifier);
    }

    public function testUserDenyBuildsAclDenyReview(): void
    {
        $capture = new \stdClass();
        $service = $this->service($capture);
        $service->review(new ManagingFieldAccessMutationReviewInput(
            new ManagingFieldAccessPolicyDescriptor(
                new ManagingFieldAccessTarget(
                    'Managing',
                    'App\\Cataloging\\Entity\\Catalog\\CatalogCategoryEntity',
                    'internalCost',
                    'detail',
                ),
                ManagingFieldPermissionVocabulary::FIELD_VIEW,
                ManagingFieldAccessPolicyDescriptor::SUBJECT_USER,
                '42',
                'deny',
            ),
            'administering:operator',
        ));

        self::assertSame('acl.deny', $capture->mutationType);
        self::assertSame('user:42', $capture->subjectIdentifier);
    }

    private function service(\stdClass $capture): AdministrationManagingFieldAccessMutationReviewService
    {
        $reviewRecorder = new class($capture) implements AdministrationAclMutationReviewRecorderInterface {
            public function __construct(private \stdClass $capture)
            {
            }

            public function record(AdministrationRollingAclMutationRequest $request, AdministrationRollingAclMutationReview $review): AdministrationAclMutationReviewRecord
            {
                $this->capture->mutationType = $request->mutationType();
                $this->capture->subjectIdentifier = $request->subjectIdentifier();

                return new AdministrationAclMutationReviewRecord(
                    'review-key',
                    $request->mutationType(),
                    $request->subjectIdentifier(),
                    $request->permissionOrRoleKey(),
                    $request->scopeKey(),
                    $request->requestedBySubject(),
                    $review->valid(),
                    $request->safeContext(),
                );
            }
        };

        return new AdministrationManagingFieldAccessMutationReviewService($reviewRecorder);
    }
}
