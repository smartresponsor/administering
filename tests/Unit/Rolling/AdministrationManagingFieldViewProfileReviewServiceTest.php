<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Rolling;

use App\Administering\Service\Managing\AdministrationManagingFieldViewProfileReviewService;
use App\Administering\Value\Rolling\AdministrationFieldViewProfileEditRequest;
use App\Administering\Value\Rolling\AdministrationManagingFieldPermissionVocabulary;
use PHPUnit\Framework\TestCase;

final class AdministrationManagingFieldViewProfileReviewServiceTest extends TestCase
{
    public function testBuildsResourceProfileReviewPayload(): void
    {
        $service = new AdministrationManagingFieldViewProfileReviewService();
        $result = $service->review(new AdministrationFieldViewProfileEditRequest(
            subjectType: AdministrationFieldViewProfileEditRequest::SUBJECT_ROLE,
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
        self::assertSame(AdministrationManagingFieldPermissionVocabulary::PROFILE_ROLE_UPDATE, $result->reviewContext['profile_permission']);
        self::assertFalse($result->toSafeArray()['safety']['grants_access']);
    }

    public function testBuildsDefaultProfileReviewPayload(): void
    {
        $service = new AdministrationManagingFieldViewProfileReviewService();
        $result = $service->review(new AdministrationFieldViewProfileEditRequest(
            subjectType: AdministrationFieldViewProfileEditRequest::SUBJECT_USER,
            subjectIdentifier: 'user:42',
            pageName: 'detail',
            visibleFields: ['description'],
            hiddenFields: [],
        ));

        self::assertSame(
            ['description'],
            $result->normalizedProfilePayload['subjects']['user:42']['defaults']['detail']['visible'],
        );
        self::assertSame(AdministrationManagingFieldPermissionVocabulary::PROFILE_USER_UPDATE, $result->reviewContext['profile_permission']);
    }

    public function testRejectsConflictingVisibleHiddenFields(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('both visible and hidden');

        (new AdministrationManagingFieldViewProfileReviewService())->review(new AdministrationFieldViewProfileEditRequest(
            subjectType: AdministrationFieldViewProfileEditRequest::SUBJECT_GROUP,
            subjectIdentifier: 'billing',
            pageName: 'index',
            visibleFields: ['status'],
            hiddenFields: ['status'],
        ));
    }
}
