<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Rolling;

use App\Administering\Service\Managing\AdministrationManagingFieldViewProfileCatalogProvider;
use App\Administering\Value\Rolling\AdministrationManagingFieldPermissionVocabulary;
use PHPUnit\Framework\TestCase;

final class AdministrationManagingFieldViewProfileCatalogProviderTest extends TestCase
{
    public function testCatalogKeepsUserProfileInsideAllowedAccessCorridor(): void
    {
        $provider = new AdministrationManagingFieldViewProfileCatalogProvider();
        $items = $provider->catalogItems();
        $userProfile = null;

        foreach ($items as $item) {
            if ($item->profileScopedByUser()) {
                $userProfile = $item;
                break;
            }
        }

        self::assertNotNull($userProfile);
        self::assertSame(AdministrationManagingFieldPermissionVocabulary::PROFILE_SELF_UPDATE, $userProfile->securityBoundary);
        self::assertStringContainsString('already allowed', $userProfile->notes);
    }

    public function testPriorityRowsKeepDenyBeforePersonalPreferences(): void
    {
        $provider = new AdministrationManagingFieldViewProfileCatalogProvider();
        $rows = $provider->priorityRows();

        self::assertSame('System/component hard deny', $rows[0]->layer);
        self::assertSame('Effective security decision', $rows[1]->layer);
        self::assertSame('User personal view profile', $rows[4]->layer);
        self::assertLessThan($rows[4]->priority, $rows[1]->priority);
        self::assertSame('allowed presentation', $rows[4]->canOverride);
    }

    public function testRuleShapeDocumentsManagingExecutionConfig(): void
    {
        $provider = new AdministrationManagingFieldViewProfileCatalogProvider();
        $shapes = $provider->ruleShapes();
        $sections = array_map(static fn ($shape): string => $shape->section, $shapes);

        self::assertContains('subjects', $sections);
        self::assertContains('resources', $sections);
        self::assertContains('visible', $sections);
        self::assertContains('hidden', $sections);
    }
}
