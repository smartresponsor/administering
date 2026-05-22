<?php

declare(strict_types=1);

namespace App\Administering\Value\Operation;

/**
 * Canonical operation keys used by the Administering orchestration surface.
 *
 * Operation payloads must remain safe metadata only. Secrets, raw .env values,
 * decrypted credentials, and source file dumps are forbidden in operation context.
 */
final class AdministrationOperationType
{
    public const CONFIGURATION_SCAN = 'administration.configuration.scan';
    public const CREDENTIAL_PRESENCE_CHECK = 'administration.credential.presence_check';
    public const SYMFONY_SECRET_SET = 'administration.symfony_secret.set';
    public const SYMFONY_SECRET_REMOVE = 'administration.symfony_secret.remove';
    public const COMPOSER_VALIDATE = 'administration.composer.validate';
    public const GENERATED_PATCH_BUILD = 'administration.generated_patch.build';
    public const ROLLING_ACL_CATALOG_REFRESH = 'administration.rolling_acl.catalog_refresh';
    public const ACCESSING_ACCOUNT_ACTION = 'administration.accessing_account.action';

    /** @return list<string> */
    public static function launchable(): array
    {
        return [
            self::CONFIGURATION_SCAN,
            self::CREDENTIAL_PRESENCE_CHECK,
            self::COMPOSER_VALIDATE,
            self::ROLLING_ACL_CATALOG_REFRESH,
            self::ACCESSING_ACCOUNT_ACTION,
        ];
    }

    public static function isLaunchable(string $operationType): bool
    {
        return in_array($operationType, self::launchable(), true);
    }

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::CONFIGURATION_SCAN,
            self::CREDENTIAL_PRESENCE_CHECK,
            self::SYMFONY_SECRET_SET,
            self::SYMFONY_SECRET_REMOVE,
            self::COMPOSER_VALIDATE,
            self::GENERATED_PATCH_BUILD,
            self::ROLLING_ACL_CATALOG_REFRESH,
            self::ACCESSING_ACCOUNT_ACTION,
        ];
    }

    public static function isKnown(string $operationType): bool
    {
        return in_array($operationType, self::all(), true);
    }

    private function __construct()
    {
    }
}
