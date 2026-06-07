<?php

declare(strict_types=1);

namespace App\Administering\Value\Managing;

final class ManagingFieldPermissionVocabulary
{
    public const FIELD_VIEW = 'managing.field.view';
    public const FIELD_CONFIGURE = 'managing.field.configure';
    public const FIELD_PROFILE_ASSIGN = 'managing.field.profile.assign';
    public const PROFILE_ROLE_UPDATE = 'managing.field.profile.role.update';
    public const PROFILE_GROUP_UPDATE = 'managing.field.profile.group.update';
    public const PROFILE_USER_UPDATE = 'managing.field.profile.user.update';
    public const PROFILE_SELF_UPDATE = 'managing.field.profile.self.update';

    /** @return list<string> */
    public static function policyKeys(): array
    {
        return [self::FIELD_VIEW, self::FIELD_CONFIGURE, self::FIELD_PROFILE_ASSIGN, self::PROFILE_ROLE_UPDATE, self::PROFILE_GROUP_UPDATE, self::PROFILE_USER_UPDATE, self::PROFILE_SELF_UPDATE];
    }
}
