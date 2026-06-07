<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Rolling;

use App\Administering\Service\Managing\AdministrationManagingFieldVisibilityInspectionPrepareService;
use App\Administering\Value\Managing\ManagingFieldVisibilityInspectionPrepareRequest;
use PHPUnit\Framework\TestCase;

final class AdministrationManagingFieldVisibilityInspectionPrepareServiceTest extends TestCase
{
    public function testPreparesManagingInspectionPayload(): void
    {
        $service = new AdministrationManagingFieldVisibilityInspectionPrepareService();
        $result = $service->prepare(new ManagingFieldVisibilityInspectionPrepareRequest(
            resourceClass: 'App\\Cataloging\\Entity\\Catalog\\CatalogCategoryEntity',
            fieldName: 'title',
            pageName: 'index',
            subjectIdentifier: 'user:42',
            statusCandidates: ['status'],
            publicationFlagCandidates: [],
            publicationDateCandidates: [],
            requestedBySubject: 'administering:operator',
        ));

        self::assertTrue($result->valid);
        self::assertSame('prepared', $result->status);
        self::assertSame('title', $result->payload['field_name']);
        self::assertSame('administering_self_contained_dry_runtime', $result->safeContext['mode']);
    }

    public function testRejectsMissingPageName(): void
    {
        $service = new AdministrationManagingFieldVisibilityInspectionPrepareService();
        $result = $service->prepare(new ManagingFieldVisibilityInspectionPrepareRequest(
            resourceClass: 'App\\Cataloging\\Entity\\Catalog\\CatalogCategoryEntity',
            fieldName: 'title',
            pageName: '',
            subjectIdentifier: null,
            statusCandidates: [],
            publicationFlagCandidates: [],
            publicationDateCandidates: [],
            requestedBySubject: 'administering:operator',
        ));

        self::assertFalse($result->valid);
        self::assertSame('rejected', $result->status);
    }
}
