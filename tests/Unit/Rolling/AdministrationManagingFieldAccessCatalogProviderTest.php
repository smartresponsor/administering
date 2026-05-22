<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Rolling;

use App\Administering\Service\Rolling\AdministrationManagingFieldAccessCatalogProvider;
use App\Administering\ServiceInterface\Rolling\AdministrationPermissionCatalogProviderInterface;
use App\Administering\Value\Rolling\AdministrationManagingFieldPermissionVocabulary;
use App\Administering\Value\Rolling\AdministrationPermissionDescriptor;
use PHPUnit\Framework\TestCase;

final class AdministrationManagingFieldAccessCatalogProviderTest extends TestCase
{
    public function testCatalogItemsExposeManagingFieldPermissions(): void
    {
        $provider = new AdministrationManagingFieldAccessCatalogProvider(new class implements AdministrationPermissionCatalogProviderInterface {
            public function descriptors(): array
            {
                return [
                    new AdministrationPermissionDescriptor(
                        AdministrationManagingFieldPermissionVocabulary::FIELD_VIEW,
                        'View Managing field',
                        'managing_field_access',
                        ['component', 'resource', 'field'],
                        true,
                    ),
                ];
            }
        });

        $items = $provider->catalogItems();
        $keys = array_map(static fn ($item): string => $item->permissionKey, $items);

        self::assertContains(AdministrationManagingFieldPermissionVocabulary::FIELD_VIEW, $keys);
        self::assertContains(AdministrationManagingFieldPermissionVocabulary::PROFILE_ASSIGN, $keys);
        self::assertTrue($items[0]->registeredInRolling);
    }

    public function testMatrixRowsKeepSecurityBeforeUserPreference(): void
    {
        $provider = new AdministrationManagingFieldAccessCatalogProvider(new class implements AdministrationPermissionCatalogProviderInterface {
            public function descriptors(): array
            {
                return [];
            }
        });

        $rows = $provider->matrixRows();

        self::assertSame('System/component hard deny', $rows[0]->layer);
        self::assertSame('User personal view profile', $rows[3]->layer);
        self::assertLessThan($rows[3]->priority, $rows[1]->priority);
    }
}
