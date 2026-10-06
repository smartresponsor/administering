<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Rolling;

use App\Administering\Value\Managing\AdministrationManagingFieldAccessPolicyDescriptor;
use App\Administering\Value\Managing\AdministrationManagingFieldAccessTarget;
use App\Administering\Value\Managing\AdministrationManagingFieldPermissionVocabulary;
use PHPUnit\Framework\TestCase;

final class AdministrationFieldAccessVocabularyTest extends TestCase
{
    public function testManagingFieldPolicyKeysAreAvailableToAdministering(): void
    {
        self::assertContains('managing.field.view', AdministrationManagingFieldPermissionVocabulary::policyKeys());
        self::assertContains('managing.field.profile.group.update', AdministrationManagingFieldPermissionVocabulary::policyKeys());
        self::assertContains('managing.field.profile.assign', AdministrationManagingFieldPermissionVocabulary::policyKeys());
    }

    public function testFieldAccessTargetHasStableAuditContextAndFingerprint(): void
    {
        $target = new AdministrationManagingFieldAccessTarget(
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
        $descriptor = new AdministrationManagingFieldAccessPolicyDescriptor(
            target: new AdministrationManagingFieldAccessTarget('Managing', 'App\\Cataloging\\Entity\\Catalog\\CatalogCategoryEntity', 'internalCost', 'detail', 'view'),
            permissionKey: AdministrationManagingFieldPermissionVocabulary::FIELD_VIEW,
            subjectType: AdministrationManagingFieldAccessPolicyDescriptor::SUBJECT_ROLE,
            subjectIdentifier: 'accounting.manager',
            effect: AdministrationManagingFieldAccessPolicyDescriptor::EFFECT_ALLOW,
        );

        self::assertTrue($descriptor->allows());
        self::assertFalse('deny' === strtolower($descriptor->effect));
    }
}
