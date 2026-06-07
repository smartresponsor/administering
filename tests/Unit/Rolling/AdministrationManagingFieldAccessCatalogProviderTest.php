<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Rolling;

use App\Administering\Provider\Managing\AdministrationManagingFieldAccessCatalogProvider;
use App\Administering\Value\Managing\ManagingFieldPermissionVocabulary;
use App\Administering\Value\Rolling\AdministrationRollingPermissionDescriptor;
use PHPUnit\Framework\TestCase;

final class AdministrationManagingFieldAccessCatalogProviderTest extends TestCase
{
    public function testCatalogItemsExposeManagingFieldPermissions(): void
    {
        $provider = new AdministrationManagingFieldAccessCatalogProvider(new class implements \App\Administering\ServiceInterface\Rolling\AdministrationRollingPermissionCatalogInterface {
            public function permissions(): array
            {
                return [ManagingFieldPermissionVocabulary::FIELD_VIEW];
            }

            public function descriptors(): array
            {
                return [
                    new AdministrationRollingPermissionDescriptor(
                        ManagingFieldPermissionVocabulary::FIELD_VIEW,
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

        self::assertContains(ManagingFieldPermissionVocabulary::FIELD_VIEW, $keys);
        self::assertContains(ManagingFieldPermissionVocabulary::FIELD_PROFILE_ASSIGN, $keys);
        self::assertTrue($items[0]->registeredInRolling);
    }

    public function testMatrixRowsKeepSecurityBeforeUserPreference(): void
    {
        $provider = new AdministrationManagingFieldAccessCatalogProvider(new class implements \App\Administering\ServiceInterface\Rolling\AdministrationRollingPermissionCatalogInterface {
            public function permissions(): array
            {
                return [];
            }

            public function descriptors(): array
            {
                return [];
            }
        });

        $rows = $provider->matrixRows();

        self::assertSame('System/component hard deny', $rows[0]->name);
        self::assertSame('User personal view profile', $rows[3]->name);
        self::assertLessThan($rows[3]->priority, $rows[1]->priority);
    }
}
