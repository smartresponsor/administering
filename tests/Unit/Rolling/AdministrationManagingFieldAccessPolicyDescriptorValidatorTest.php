<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Rolling;

use App\Administering\Validator\Rolling\AdministrationManagingFieldAccessPolicyDescriptorValidator;
use App\Administering\Value\Rolling\AdministrationFieldAccessPolicyDescriptor;
use App\Administering\Value\Rolling\AdministrationFieldAccessTarget;
use App\Administering\Value\Rolling\AdministrationManagingFieldPermissionVocabulary;
use PHPUnit\Framework\TestCase;

final class AdministrationManagingFieldAccessPolicyDescriptorValidatorTest extends TestCase
{
    public function testValidManagingFieldViewDescriptorPasses(): void
    {
        $validator = new AdministrationManagingFieldAccessPolicyDescriptorValidator();
        $validator->assertValid($this->descriptor(AdministrationManagingFieldPermissionVocabulary::FIELD_VIEW));

        self::addToAssertionCount(1);
    }

    public function testProfilePermissionCannotBeUsedAsFieldValueAccessGrant(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('managing.field.view');

        (new AdministrationManagingFieldAccessPolicyDescriptorValidator())->assertValid(
            $this->descriptor(AdministrationManagingFieldPermissionVocabulary::PROFILE_SELF_UPDATE),
        );
    }

    public function testNonManagingComponentIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Managing component');

        (new AdministrationManagingFieldAccessPolicyDescriptorValidator())->assertValid(new AdministrationFieldAccessPolicyDescriptor(
            new AdministrationFieldAccessTarget('Cataloging', 'App\\Cataloging\\Entity\\Product', 'internalCost', 'detail'),
            AdministrationManagingFieldPermissionVocabulary::FIELD_VIEW,
            AdministrationFieldAccessPolicyDescriptor::SUBJECT_ROLE,
            'catalog.manager',
            AdministrationFieldAccessPolicyDescriptor::EFFECT_ALLOW,
        ));
    }

    private function descriptor(string $permissionKey): AdministrationFieldAccessPolicyDescriptor
    {
        return new AdministrationFieldAccessPolicyDescriptor(
            new AdministrationFieldAccessTarget(
                'Managing',
                'App\\Cataloging\\Entity\\Catalog\\CatalogCategoryEntity',
                'internalCost',
                'detail',
            ),
            $permissionKey,
            AdministrationFieldAccessPolicyDescriptor::SUBJECT_ROLE,
            'catalog.manager',
            AdministrationFieldAccessPolicyDescriptor::EFFECT_ALLOW,
        );
    }
}
