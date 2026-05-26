<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Rolling;

use App\Administering\Entity\AdministrationAclMutationReviewRecord;
use App\Administering\Service\Managing\AdministrationManagingFieldAccessMutationReviewService;
use App\Administering\ServiceInterface\Rolling\AdministrationAclMutationReviewRecorderInterface;
use App\Managing\ServiceInterface\Administration\ManagingFieldAccessMutationReviewServiceInterface as OwnerReviewServiceInterface;
use App\Managing\Value\Administration\ManagingFieldAccessMutationReviewInput as OwnerReviewInput;
use App\Managing\Value\Administration\ManagingFieldAccessMutationReviewResult as OwnerReviewResult;
use App\Managing\Value\Administration\ManagingFieldAccessPolicyDescriptor;
use App\Managing\Value\Administration\ManagingFieldAccessTarget;
use App\Rolling\Value\Administration\RollingAclMutationReview;
use PHPUnit\Framework\TestCase;

final class AdministrationManagingFieldAccessMutationReviewServiceTest extends TestCase
{
    public function testRoleAllowBuildsPermissionGrantReview(): void
    {
        $capture = new \stdClass();
        $service = $this->service($capture);
        $result = $service->review(new OwnerReviewInput(
            new ManagingFieldAccessPolicyDescriptor(
                new ManagingFieldAccessTarget(
                    'Managing',
                    'App\\Cataloging\\Entity\\Catalog\\CatalogCategoryEntity',
                    'internalCost',
                    'detail',
                ),
                \App\Managing\Value\Administration\ManagingFieldPermissionVocabulary::FIELD_VIEW,
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
        $service->review(new OwnerReviewInput(
            new ManagingFieldAccessPolicyDescriptor(
                new ManagingFieldAccessTarget(
                    'Managing',
                    'App\\Cataloging\\Entity\\Catalog\\CatalogCategoryEntity',
                    'internalCost',
                    'detail',
                ),
                \App\Managing\Value\Administration\ManagingFieldPermissionVocabulary::FIELD_VIEW,
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
        $ownerService = new class($capture) implements OwnerReviewServiceInterface {
            public function __construct(private \stdClass $capture)
            {
            }

            public function review(OwnerReviewInput $input): OwnerReviewResult
            {
                $descriptor = $input->descriptor;
                $this->capture->mutationType = 'role' === $descriptor->subjectType
                    ? ('allow' === $descriptor->effect ? 'permission.grant' : 'permission.revoke')
                    : ('allow' === $descriptor->effect ? 'acl.allow' : 'acl.deny');
                $this->capture->subjectIdentifier = 'role' === $descriptor->subjectType
                    ? trim($descriptor->subjectIdentifier)
                    : 'user:'.trim($descriptor->subjectIdentifier);

                $review = new RollingAclMutationReview(
                    $this->capture->mutationType,
                    $this->capture->subjectIdentifier,
                    $descriptor->permissionKey,
                    'component:managing',
                    true,
                );

                $this->capture->review = $review;

                return new OwnerReviewResult($descriptor, $review);
            }
        };

        $reviewRecorder = new class($capture) implements AdministrationAclMutationReviewRecorderInterface {
            public function __construct(private \stdClass $capture)
            {
            }

            public function record(\App\Rolling\Value\Administration\RollingAclMutationRequest $request, RollingAclMutationReview $review): AdministrationAclMutationReviewRecord
            {
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

        return new AdministrationManagingFieldAccessMutationReviewService($ownerService, $reviewRecorder);
    }
}
