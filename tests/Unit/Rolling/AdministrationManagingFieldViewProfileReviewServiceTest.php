<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Rolling;

use App\Administering\Service\Managing\AdministrationManagingFieldViewProfileReviewService;
use App\Managing\Service\Administration\ManagingFieldViewProfileReviewService;
use App\Managing\Value\Administration\ManagingFieldPermissionVocabulary;
use App\Managing\Value\Administration\ManagingFieldViewProfileEditRequest;
use PHPUnit\Framework\TestCase;

final class AdministrationManagingFieldViewProfileReviewServiceTest extends TestCase
{
    public function testBuildsResourceProfileReviewPayload(): void
    {
        $service = new AdministrationManagingFieldViewProfileReviewService(new ManagingFieldViewProfileReviewService());
        $result = $service->review(new ManagingFieldViewProfileEditRequest(
            subjectType: ManagingFieldViewProfileEditRequest::SUBJECT_ROLE,
            subjectIdentifier: 'security.admin',
            pageName: 'index',
            visibleFields: ['title', 'status', 'title'],
            hiddenFields: ['createdAt'],
            resourceClass: 'App\\Cataloging\\Entity\\Catalog\\CatalogCategoryEntity',
            reason: 'Owner approved table cleanup.',
            requestedBySubject: 'administering:operator',
        ));

        self::assertSame('managing.field_view_profile.review', $result->changeType);
        self::assertStringContainsString('role:security.admin', $result->targetReference);
        self::assertSame(
            ['title', 'status'],
            $result->normalizedProfilePayload['subjects']['role:security.admin']['resources']['App\\Cataloging\\Entity\\Catalog\\CatalogCategoryEntity']['index']['visible'],
        );
        self::assertSame(
            ['createdAt'],
            $result->normalizedProfilePayload['subjects']['role:security.admin']['resources']['App\\Cataloging\\Entity\\Catalog\\CatalogCategoryEntity']['index']['hidden'],
        );
        self::assertSame(ManagingFieldPermissionVocabulary::PROFILE_ROLE_UPDATE, $result->reviewContext['profile_permission']);
        self::assertFalse($result->toSafeArray()['safety']['grants_access']);
    }

    public function testBuildsDefaultProfileReviewPayload(): void
    {
        $service = new AdministrationManagingFieldViewProfileReviewService(new ManagingFieldViewProfileReviewService());
        $result = $service->review(new ManagingFieldViewProfileEditRequest(
            subjectType: ManagingFieldViewProfileEditRequest::SUBJECT_USER,
            subjectIdentifier: 'user:42',
            pageName: 'detail',
            visibleFields: ['description'],
            hiddenFields: [],
        ));

        self::assertSame(
            ['description'],
            $result->normalizedProfilePayload['subjects']['user:42']['defaults']['detail']['visible'],
        );
        self::assertSame(ManagingFieldPermissionVocabulary::PROFILE_USER_UPDATE, $result->reviewContext['profile_permission']);
    }

    public function testRejectsConflictingVisibleHiddenFields(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('both visible and hidden');

        (new AdministrationManagingFieldViewProfileReviewService(new ManagingFieldViewProfileReviewService()))->review(new ManagingFieldViewProfileEditRequest(
            subjectType: ManagingFieldViewProfileEditRequest::SUBJECT_GROUP,
            subjectIdentifier: 'billing',
            pageName: 'index',
            visibleFields: ['status'],
            hiddenFields: ['status'],
        ));
    }
}
