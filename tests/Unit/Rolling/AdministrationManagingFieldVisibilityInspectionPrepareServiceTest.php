<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Rolling;

use App\Administering\Service\Managing\AdministrationManagingFieldVisibilityInspectionPrepareService;
use App\Managing\Service\Administration\ManagingFieldVisibilityInspectionPrepareService;
use App\Managing\Value\Administration\ManagingFieldVisibilityInspectionPrepareRequest;
use PHPUnit\Framework\TestCase;

final class AdministrationManagingFieldVisibilityInspectionPrepareServiceTest extends TestCase
{
    public function testPreparesManagingInspectionPayload(): void
    {
        $service = new AdministrationManagingFieldVisibilityInspectionPrepareService(new ManagingFieldVisibilityInspectionPrepareService());
        $result = $service->prepare(new ManagingFieldVisibilityInspectionPrepareRequest(
            resourceClass: 'App\\Cataloging\\Entity\\Catalog\\CatalogCategoryEntity',
            fieldName: 'title',
            pageName: 'index',
            subjectIdentifier: 'user:42',
            statusCandidates: ['status'],
            requestedBySubject: 'administering:operator',
        ));

        self::assertTrue($result->accepted);
        self::assertSame('field_visibility_inspection_payload_prepared', $result->reason);
        self::assertSame('title', $result->managingInspectionPayload['fieldName']);
        self::assertSame('App\\Managing\\InspectorInterface\\Crud\\ManageCrudFieldVisibilityInspectorInterface', $result->toSafeArray()['managing_inspection_contract']);
        self::assertFalse($result->toSafeArray()['safety']['grants_access']);
    }

    public function testRejectsInvalidPageName(): void
    {
        $service = new AdministrationManagingFieldVisibilityInspectionPrepareService(new ManagingFieldVisibilityInspectionPrepareService());
        $result = $service->prepare(new ManagingFieldVisibilityInspectionPrepareRequest(
            resourceClass: 'App\\Cataloging\\Entity\\Catalog\\CatalogCategoryEntity',
            fieldName: 'title',
            pageName: 'delete',
        ));

        self::assertFalse($result->accepted);
        self::assertSame('field_visibility_inspection_invalid_page_name', $result->reason);
    }
}
