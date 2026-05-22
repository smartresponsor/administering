<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Rolling;

use App\Administering\Service\Rolling\AdministrationManagingFieldVisibilityInspectionPrepareService;
use App\Administering\Value\Rolling\AdministrationFieldVisibilityInspectionPrepareRequest;
use PHPUnit\Framework\TestCase;

final class AdministrationManagingFieldVisibilityInspectionPrepareServiceTest extends TestCase
{
    public function testPreparesManagingInspectionPayload(): void
    {
        $service = new AdministrationManagingFieldVisibilityInspectionPrepareService();
        $result = $service->prepare(new AdministrationFieldVisibilityInspectionPrepareRequest(
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
        $service = new AdministrationManagingFieldVisibilityInspectionPrepareService();
        $result = $service->prepare(new AdministrationFieldVisibilityInspectionPrepareRequest(
            resourceClass: 'App\\Cataloging\\Entity\\Catalog\\CatalogCategoryEntity',
            fieldName: 'title',
            pageName: 'delete',
        ));

        self::assertFalse($result->accepted);
        self::assertSame('field_visibility_inspection_invalid_page_name', $result->reason);
    }
}
