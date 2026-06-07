<?php

declare(strict_types=1);

namespace App\Administering\Value\Form\Managing;

/**
 * Administering-owned Managing field permission keys.
 *
 * This value class keeps the dry-runtime container self-contained. The optional
 * Managing component may expose the same vocabulary from its own namespace in a
 * host application, but Administering must not require that component during
 * base container reflection.
 */
final class AdministrationManagingFieldPermissionVocabulary
{
    public const FIELD_VIEW = 'managing.field.view';
    public const FIELD_CONFIGURE = 'managing.field.configure';
    public const PROFILE_ROLE_UPDATE = 'managing.field.profile.role_update';
    public const PROFILE_GROUP_UPDATE = 'managing.field.profile.group_update';
    public const PROFILE_USER_UPDATE = 'managing.field.profile.user_update';
    public const PROFILE_SELF_UPDATE = 'managing.field.profile.self_update';

    /**
     * @return list<string>
     */
    public static function policyKeys(): array
    {
        return [
            self::FIELD_VIEW,
            self::FIELD_CONFIGURE,
            self::PROFILE_ROLE_UPDATE,
            self::PROFILE_GROUP_UPDATE,
            self::PROFILE_USER_UPDATE,
            self::PROFILE_SELF_UPDATE,
        ];
    }
}
