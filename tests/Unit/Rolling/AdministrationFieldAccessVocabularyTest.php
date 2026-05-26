<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Rolling;

use App\Managing\Value\Administration\ManagingFieldAccessPolicyDescriptor;
use App\Managing\Value\Administration\ManagingFieldAccessTarget;
use App\Managing\Value\Administration\ManagingFieldPermissionVocabulary;
use PHPUnit\Framework\TestCase;

final class AdministrationFieldAccessVocabularyTest extends TestCase
{
    public function testManagingFieldPolicyKeysAreAvailableToAdministering(): void
    {
        self::assertContains('managing.field.view', ManagingFieldPermissionVocabulary::policyKeys());
        self::assertContains('managing.field.profile.group_update', ManagingFieldPermissionVocabulary::policyKeys());
        self::assertContains('managing.field.profile.assign', ManagingFieldPermissionVocabulary::policyKeys());
    }

    public function testFieldAccessTargetHasStableAuditContextAndFingerprint(): void
    {
        $target = new ManagingFieldAccessTarget(
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
        $descriptor = new ManagingFieldAccessPolicyDescriptor(
            target: new ManagingFieldAccessTarget('Managing', 'App\\Cataloging\\Entity\\Catalog\\CatalogCategoryEntity', 'internalCost', 'detail'),
            permissionKey: ManagingFieldPermissionVocabulary::FIELD_VIEW,
            subjectType: ManagingFieldAccessPolicyDescriptor::SUBJECT_ROLE,
            subjectIdentifier: 'accounting.manager',
            effect: ManagingFieldAccessPolicyDescriptor::EFFECT_ALLOW,
        );

        self::assertTrue($descriptor->allows());
        self::assertFalse($descriptor->denies());
    }
}
