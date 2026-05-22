<?php

declare(strict_types=1);

namespace App\Administering\Validator\Rolling;

use App\Administering\ValidatorInterface\Rolling\AdministrationFieldAccessPolicyDescriptorValidatorInterface;
use App\Administering\Value\Rolling\AdministrationFieldAccessPolicyDescriptor;
use App\Administering\Value\Rolling\AdministrationManagingFieldPermissionVocabulary;

/**
 * Hardens the Administering review corridor for Managing field-value access policies.
 *
 * Field access mutations grant or deny access to field values only. Profile preferences and configuration rights are
 * separate control-plane capabilities and must not enter this mutation path.
 */
final readonly class AdministrationManagingFieldAccessPolicyDescriptorValidator implements AdministrationFieldAccessPolicyDescriptorValidatorInterface
{
    public function assertValid(AdministrationFieldAccessPolicyDescriptor $descriptor): void
    {
        $this->assertFieldAccessPermission($descriptor->permissionKey);
        $this->assertTarget($descriptor);
        $this->assertSubject($descriptor);
        $this->assertEffect($descriptor->effect);
    }

    private function assertFieldAccessPermission(string $permissionKey): void
    {
        if (AdministrationManagingFieldPermissionVocabulary::FIELD_VIEW !== trim($permissionKey)) {
            throw new \InvalidArgumentException('Managing field access mutations may only use managing.field.view.');
        }
    }

    private function assertTarget(AdministrationFieldAccessPolicyDescriptor $descriptor): void
    {
        $target = $descriptor->target;
        if ('managing' !== strtolower(trim($target->componentKey))) {
            throw new \InvalidArgumentException('Managing field access target must use the Managing component.');
        }

        foreach (['resourceClass' => $target->resourceClass, 'fieldName' => $target->fieldName, 'pageName' => $target->pageName] as $name => $value) {
            if ('' === trim($value)) {
                throw new \InvalidArgumentException(sprintf('Managing field access target %s is required.', $name));
            }
        }

        if ('view' !== trim($target->operation)) {
            throw new \InvalidArgumentException('Managing field access value grants must use the view operation.');
        }
    }

    private function assertSubject(AdministrationFieldAccessPolicyDescriptor $descriptor): void
    {
        if (!in_array($descriptor->subjectType, [
            AdministrationFieldAccessPolicyDescriptor::SUBJECT_USER,
            AdministrationFieldAccessPolicyDescriptor::SUBJECT_ROLE,
            AdministrationFieldAccessPolicyDescriptor::SUBJECT_GROUP,
        ], true)) {
            throw new \InvalidArgumentException('Managing field access subject type must be user, role, or group.');
        }

        if ('' === trim($descriptor->subjectIdentifier)) {
            throw new \InvalidArgumentException('Managing field access subject identifier is required.');
        }
    }

    private function assertEffect(string $effect): void
    {
        if (!in_array($effect, [
            AdministrationFieldAccessPolicyDescriptor::EFFECT_ALLOW,
            AdministrationFieldAccessPolicyDescriptor::EFFECT_DENY,
        ], true)) {
            throw new \InvalidArgumentException('Managing field access effect must be allow or deny.');
        }
    }
}
