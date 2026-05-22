<?php

declare(strict_types=1);

namespace App\Administering\Value\Rolling;

/**
 * Describes an administrative field policy assignment without binding Administering to EasyAdmin internals.
 */
final readonly class AdministrationFieldAccessPolicyDescriptor
{
    public const SUBJECT_USER = 'user';
    public const SUBJECT_ROLE = 'role';
    public const SUBJECT_GROUP = 'group';

    public const EFFECT_ALLOW = 'allow';
    public const EFFECT_DENY = 'deny';

    public function __construct(
        public AdministrationFieldAccessTarget $target,
        public string $permissionKey,
        public string $subjectType,
        public string $subjectIdentifier,
        public string $effect,
        public ?string $reason = null,
    ) {
    }

    public function allows(): bool
    {
        return self::EFFECT_ALLOW === $this->effect;
    }

    public function denies(): bool
    {
        return self::EFFECT_DENY === $this->effect;
    }
}
