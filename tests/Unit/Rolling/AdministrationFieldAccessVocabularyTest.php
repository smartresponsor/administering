<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Rolling;

use App\Administering\Value\Managing\ManagingFieldAccessPolicyDescriptor;
use App\Administering\Value\Managing\ManagingFieldAccessTarget;
use App\Administering\Value\Managing\ManagingFieldPermissionVocabulary;
use PHPUnit\Framework\TestCase;

final class AdministrationFieldAccessVocabularyTest extends TestCase
{
    public function testManagingFieldPolicyKeysAreAvailableToAdministering(): void
    {
        self::assertContains('managing.field.view', ManagingFieldPermissionVocabulary::policyKeys());
        self::assertContains('managing.field.profile.group.update', ManagingFieldPermissionVocabulary::policyKeys());
        self::assertContains('managing.field.profile.assign', ManagingFieldPermissionVocabulary::policyKeys());
    }

    public function testFieldAccessTargetHasStableAuditContextAndFingerprint(): void
    {
        $target = new ManagingFieldAccessTarget(
            componentKey: 'Managing',
            resourceClass: 'App\\Cataloging\\Entity\\Catalog\\CatalogCategoryEntity',
            fieldName: 'internalCost',
            pageName: 'detail',
            operation: 'view',
        );

        self::assertSame('internalCost', $target->toSafeArray()['field_name']);
        self::assertSame('view', $target->toSafeArray()['operation']);
    }

    public function testPolicyDescriptorKeepsAdminEffectSeparateFromUserPreference(): void
    {
        $descriptor = new ManagingFieldAccessPolicyDescriptor(
            target: new ManagingFieldAccessTarget('Managing', 'App\\Cataloging\\Entity\\Catalog\\CatalogCategoryEntity', 'internalCost', 'detail', 'view'),
            permissionKey: ManagingFieldPermissionVocabulary::FIELD_VIEW,
            subjectType: ManagingFieldAccessPolicyDescriptor::SUBJECT_ROLE,
            subjectIdentifier: 'accounting.manager',
            effect: ManagingFieldAccessPolicyDescriptor::EFFECT_ALLOW,
        );

        self::assertTrue($descriptor->allows());
        self::assertFalse('deny' === strtolower($descriptor->effect));
    }
}
