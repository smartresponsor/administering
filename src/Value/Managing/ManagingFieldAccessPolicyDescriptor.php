<?php

declare(strict_types=1);

namespace App\Administering\Value\Managing;

final readonly class ManagingFieldAccessPolicyDescriptor
{
    public const SUBJECT_ROLE = 'role';
    public const SUBJECT_USER = 'user';
    public const EFFECT_ALLOW = 'allow';
    public const EFFECT_DENY = 'deny';

    public function __construct(
        public ManagingFieldAccessTarget $target,
        public string $permissionKey,
        public string $subjectType,
        public string $subjectIdentifier,
        public string $effect,
        public ?string $reason = null,
    ) {
    }

    public function allows(): bool
    {
        return 'allow' === strtolower(trim($this->effect));
    }

    /** @return array<string, mixed> */
    public function toSafeArray(): array
    {
        return [
            'target' => $this->target->toSafeArray(),
            'permission_key' => $this->permissionKey,
            'subject_type' => $this->subjectType,
            'subject_identifier' => $this->subjectIdentifier,
            'effect' => $this->effect,
            'reason' => $this->reason,
        ];
    }
}
