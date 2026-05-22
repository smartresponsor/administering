<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Rolling;

use App\Administering\Value\Rolling\AdministrationFieldAccessPolicyDescriptor;
use App\Administering\Value\Rolling\AdministrationFieldAccessTarget;
use App\Administering\Value\Rolling\AdministrationManagingFieldPermissionVocabulary;
use PHPUnit\Framework\TestCase;

final class AdministrationFieldAccessVocabularyTest extends TestCase
{
    public function testManagingFieldPolicyKeysAreAvailableToAdministering(): void
    {
        self::assertContains('managing.field.view', AdministrationManagingFieldPermissionVocabulary::policyKeys());
        self::assertContains('managing.field.profile.group_update', AdministrationManagingFieldPermissionVocabulary::policyKeys());
        self::assertContains('managing.field.profile.assign', AdministrationManagingFieldPermissionVocabulary::policyKeys());
    }

    public function testFieldAccessTargetHasStableAuditContextAndFingerprint(): void
    {
        $target = new AdministrationFieldAccessTarget(
            componentKey: 'Managing',
            resourceClass: 'App\\Cataloging\\Entity\\Catalog\\CatalogCategoryEntity',
            fieldName: 'internalCost',
            pageName: 'detail',
        );

        self::assertSame('Managing:App.Cataloging.Entity.Catalog.CatalogCategoryEntity:internalCost:detail:view', $target->fingerprint());
        self::assertSame('internalCost', $target->toAuditContext()['field']);
    }

    public function testPolicyDescriptorKeepsAdminEffectSeparateFromUserPreference(): void
    {
        $descriptor = new AdministrationFieldAccessPolicyDescriptor(
            target: new AdministrationFieldAccessTarget('Managing', 'App\\Cataloging\\Entity\\Catalog\\CatalogCategoryEntity', 'internalCost', 'detail'),
            permissionKey: AdministrationManagingFieldPermissionVocabulary::FIELD_VIEW,
            subjectType: AdministrationFieldAccessPolicyDescriptor::SUBJECT_ROLE,
            subjectIdentifier: 'accounting.manager',
            effect: AdministrationFieldAccessPolicyDescriptor::EFFECT_ALLOW,
        );

        self::assertTrue($descriptor->allows());
        self::assertFalse($descriptor->denies());
    }
}
