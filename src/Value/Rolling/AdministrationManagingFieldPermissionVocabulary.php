<?php

declare(strict_types=1);

namespace App\Administering\Value\Rolling;

/**
 * Administering-local vocabulary for Managing field access and field visibility profile administration.
 *
 * Administering presents and audits these permissions; Rolling owns effective authorization decisions; Managing
 * enforces the final field filtering before EasyAdmin renders CRUD pages.
 */
final readonly class AdministrationManagingFieldPermissionVocabulary
{
    public const FIELD_VIEW = 'managing.field.view';
    public const FIELD_CONFIGURE = 'managing.field.configure';
    public const PROFILE_SELF_UPDATE = 'managing.field.profile.self_update';
    public const PROFILE_USER_UPDATE = 'managing.field.profile.user_update';
    public const PROFILE_ROLE_UPDATE = 'managing.field.profile.role_update';
    public const PROFILE_GROUP_UPDATE = 'managing.field.profile.group_update';
    public const PROFILE_ASSIGN = 'managing.field.profile.assign';

    /** @return list<string> */
    public static function policyKeys(): array
    {
        return [
            self::FIELD_VIEW,
            self::FIELD_CONFIGURE,
            self::PROFILE_SELF_UPDATE,
            self::PROFILE_USER_UPDATE,
            self::PROFILE_ROLE_UPDATE,
            self::PROFILE_GROUP_UPDATE,
            self::PROFILE_ASSIGN,
        ];
    }
}
