<?php

declare(strict_types=1);

namespace App\Administering\Value\RuntimeScope;

/**
 * Normalized row shared by runtime-scope CLI reports and connected-component JSON surfaces.
 */
final readonly class AdministrationRuntimeScopeDecisionRow
{
    /** @param array<string, mixed> $evidence */
    public function __construct(
        public string $component,
        public bool $present,
        public bool $allowed,
        public bool $locked,
        public bool $enabled,
        public bool $disabled,
        public string $status,
        public string $reason,
        public string $message,
        public ?string $runtimeScope,
        public ?string $composerPackage,
        public ?string $bundleToken,
        public array $evidence = [],
    ) {
    }

    public static function fromStatus(AdministrationRuntimeComponentStatus $status, ?string $runtimeScopeRaw): self
    {
        $message = self::messageForStatus($status->status);
        $enabled = 'available' === $status->status;

        return new self(
            component: $status->componentKey,
            present: $status->composerPackageInstalled,
            allowed: $status->inRuntimeScope,
            locked: $status->lockEnabled,
            enabled: $enabled,
            disabled: $status->lockDisabled,
            status: $status->status,
            reason: $message,
            message: $message,
            runtimeScope: self::nullableString($status->evidence['runtimeScope'] ?? $runtimeScopeRaw),
            composerPackage: self::nullableString($status->evidence['composerPackage'] ?? null),
            bundleToken: self::nullableString($status->evidence['bundleToken'] ?? null),
            evidence: $status->toArray(),
        );
    }

    public static function standalone(?string $runtimeScopeRaw): self
    {
        $message = 'Administering standalone scope is active.';

        return new self(
            component: 'administering',
            present: true,
            allowed: true,
            locked: true,
            enabled: true,
            disabled: false,
            status: 'available',
            reason: $message,
            message: $message,
            runtimeScope: $runtimeScopeRaw ?? '',
            composerPackage: 'administering/administering',
            bundleToken: 'administering.bundle',
            evidence: [],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'component' => $this->component,
            'present' => $this->present,
            'allowed' => $this->allowed,
            'locked' => $this->locked,
            'enabled' => $this->enabled,
            'disabled' => $this->disabled,
            'status' => $this->status,
            'reason' => $this->reason,
            'message' => $this->message,
            'inRuntimeScope' => $this->allowed,
            'composerPackageInstalled' => $this->present,
            'lockEnabled' => $this->locked,
            'lockDisabled' => $this->disabled,
            'runtimeScope' => $this->runtimeScope,
            'composerPackage' => $this->composerPackage,
            'bundleToken' => $this->bundleToken,
            'evidence' => $this->evidence,
        ];
    }

    /** @return array<string, mixed> */
    public function evidenceContext(): array
    {
        return [
            'decision' => [
                'present' => $this->present,
                'allowed' => $this->allowed,
                'locked' => $this->locked,
                'enabled' => $this->enabled,
                'disabled' => $this->disabled,
                'status' => $this->status,
                'reason' => $this->reason,
            ],
            'runtimeScope' => $this->runtimeScope,
            'composerPackage' => $this->composerPackage,
            'bundleToken' => $this->bundleToken,
            'rawEvidence' => $this->evidence,
        ];
    }

    private static function messageForStatus(string $status): string
    {
        return match ($status) {
            'available' => 'Component is inside Composer capability boundary and enabled by runtime lock evidence.',
            'package_installed' => 'Composer package is present inside the capability boundary, but runtime lock did not enable the component.',
            'missing_package' => 'Runtime lock references a component whose package is not present in composer inventory.',
            'disabled_by_lock' => 'Component is explicitly disabled by runtime-scope lock evidence.',
            'out_of_scope' => 'Component is outside the Composer capability boundary.',
            default => 'Component status is derived from runtime evidence.',
        };
    }

    private static function nullableString(mixed $value): ?string
    {
        if (null === $value) {
            return null;
        }

        return (string) $value;
    }
}
