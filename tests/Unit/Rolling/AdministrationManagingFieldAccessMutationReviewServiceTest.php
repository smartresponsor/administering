<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Rolling;

use App\Administering\Entity\AdministrationAclMutationReviewRecord;
use App\Administering\Service\Managing\AdministrationManagingFieldAccessMutationReviewService;
use App\Administering\ServiceInterface\Rolling\AdministrationAclMutationReviewRecorderInterface;
use App\Administering\Validator\Rolling\AdministrationManagingFieldAccessPolicyDescriptorValidator;
use App\Administering\Value\Rolling\AdministrationFieldAccessMutationReviewInput;
use App\Administering\Value\Rolling\AdministrationFieldAccessPolicyDescriptor;
use App\Administering\Value\Rolling\AdministrationFieldAccessTarget;
use App\Administering\Value\Rolling\AdministrationManagingFieldPermissionVocabulary;
use App\Rolling\ServiceInterface\Administration\RollingAclMutationReviewBuilderInterface;
use App\Rolling\Value\Administration\RollingAclMutationRequest;
use App\Rolling\Value\Administration\RollingAclMutationReview;
use PHPUnit\Framework\TestCase;

final class AdministrationManagingFieldAccessMutationReviewServiceTest extends TestCase
{
    public function testRoleAllowBuildsPermissionGrantReview(): void
    {
        $capture = new \stdClass();
        $service = $this->service($capture);
        $result = $service->review(new AdministrationFieldAccessMutationReviewInput(
            $this->descriptor(AdministrationFieldAccessPolicyDescriptor::SUBJECT_ROLE, 'security.admin', 'allow'),
            'administering:operator',
        ));

        self::assertInstanceOf(RollingAclMutationRequest::class, $capture->request);
        self::assertSame('permission.grant', $capture->request->mutationType());
        self::assertSame('security.admin', $capture->request->subjectIdentifier());
        self::assertSame(AdministrationManagingFieldPermissionVocabulary::FIELD_VIEW, $capture->request->permissionOrRoleKey());
        self::assertStringContainsString('field:internalcost', $capture->request->scopeKey());
        self::assertSame('review-key', $result->record->requestKey());
    }

    public function testUserDenyBuildsAclDenyReview(): void
    {
        $capture = new \stdClass();
        $service = $this->service($capture);
        $service->review(new AdministrationFieldAccessMutationReviewInput(
            $this->descriptor(AdministrationFieldAccessPolicyDescriptor::SUBJECT_USER, '42', 'deny'),
            'administering:operator',
        ));

        self::assertInstanceOf(RollingAclMutationRequest::class, $capture->request);
        self::assertSame('acl.deny', $capture->request->mutationType());
        self::assertSame('user:42', $capture->request->subjectIdentifier());
    }

    private function descriptor(string $subjectType, string $subjectIdentifier, string $effect): AdministrationFieldAccessPolicyDescriptor
    {
        return new AdministrationFieldAccessPolicyDescriptor(
            new AdministrationFieldAccessTarget(
                'Managing',
                'App\\Cataloging\\Entity\\Catalog\\CatalogCategoryEntity',
                'internalCost',
                'detail',
            ),
            AdministrationManagingFieldPermissionVocabulary::FIELD_VIEW,
            $subjectType,
            $subjectIdentifier,
            $effect,
        );
    }

    private function service(\stdClass $capture): AdministrationManagingFieldAccessMutationReviewService
    {
        $reviewBuilder = new class($capture) implements RollingAclMutationReviewBuilderInterface {
            public function __construct(private \stdClass $capture)
            {
            }

            public function review(RollingAclMutationRequest $request): RollingAclMutationReview
            {
                $this->capture->request = $request;

                return new RollingAclMutationReview(
                    $request->mutationType(),
                    $request->subjectIdentifier(),
                    $request->permissionOrRoleKey(),
                    $request->scopeKey(),
                    true,
                );
            }
        };

        $recorder = new class implements AdministrationAclMutationReviewRecorderInterface {
            public function record(RollingAclMutationRequest $request, RollingAclMutationReview $review): AdministrationAclMutationReviewRecord
            {
                return new AdministrationAclMutationReviewRecord(
                    'review-key',
                    $review->mutationType(),
                    $review->subjectIdentifier(),
                    $review->permissionOrRoleKey(),
                    $review->scopeKey(),
                    $request->requestedBySubject(),
                    $review->valid(),
                    $review->toSafeArray(),
                );
            }
        };

        return new AdministrationManagingFieldAccessMutationReviewService($reviewBuilder, $recorder, new AdministrationManagingFieldAccessPolicyDescriptorValidator());
    }
}
