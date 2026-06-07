<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Rolling;

use App\Administering\Provider\Managing\AdministrationManagingFieldViewProfileCatalogProvider;
use PHPUnit\Framework\TestCase;

final class AdministrationManagingFieldViewProfileCatalogProviderTest extends TestCase
{
    public function testCatalogKeepsUserProfileInsideAllowedAccessCorridor(): void
    {
        $provider = new AdministrationManagingFieldViewProfileCatalogProvider();
        $items = $provider->catalogItems();
        $userProfile = null;

        foreach ($items as $item) {
            if ('user' === $item->profileKey) {
                $userProfile = $item;
                break;
            }
        }

        self::assertNotNull($userProfile);
        self::assertTrue($userProfile->userEditable);
        self::assertStringContainsString('already allowed', (string) $userProfile->description);
    }

    public function testPriorityRowsKeepDenyBeforePersonalPreferences(): void
    {
        $provider = new AdministrationManagingFieldViewProfileCatalogProvider();
        $rows = $provider->priorityRows();

        self::assertSame('System/component hard deny', $rows[0]->name);
        self::assertSame('Effective security decision', $rows[1]->name);
        self::assertSame('User personal view profile', $rows[4]->name);
        self::assertLessThan($rows[4]->priority, $rows[1]->priority);
        self::assertSame('allowed presentation', $rows[4]->effect);
    }

    public function testRuleShapeDocumentsManagingExecutionConfig(): void
    {
        $provider = new AdministrationManagingFieldViewProfileCatalogProvider();
        $shapes = $provider->ruleShapes();
        $sections = array_map(static fn ($shape): string => $shape->key, $shapes);

        self::assertContains('subjects', $sections);
        self::assertContains('resources', $sections);
        self::assertContains('visible', $sections);
        self::assertContains('hidden', $sections);
    }
}
