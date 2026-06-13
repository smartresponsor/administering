<?php

declare(strict_types=1);

namespace App\Administering\Lifecycle;

/**
 * Lifecycle guard for administration operation.
 *
 * The policy is intentionally framework-free: entities/services can call it
 * without introducing cross-component Doctrine dependencies.
 */
final class AdministrationOperationLifecyclePolicy
{
    /** @var array<string, list<string>> */
    private const TRANSITIONS = [
        'requested' => ['approved', 'rejected', 'cancelled'],
        'approved' => ['scheduled', 'running', 'cancelled'],
        'scheduled' => ['running', 'cancelled', 'expired'],
        'running' => ['succeeded', 'failed', 'cancelled'],
        'failed' => ['retrying', 'abandoned'],
        'retrying' => ['running', 'abandoned'],
        'succeeded' => [],
        'rejected' => [],
        'cancelled' => [],
        'expired' => [],
        'abandoned' => [],
    ];

    public function canTransition(string $from, string $to): bool
    {
        $from = self::normalize($from);
        $to = self::normalize($to);

        if ($from === $to) {
            return true;
        }

        return \in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public function assertCanTransition(string $from, string $to): void
    {
        if (!$this->canTransition($from, $to)) {
            throw new \DomainException(sprintf('Invalid administration operation lifecycle transition from "%s" to "%s".', $from, $to));
        }
    }

    /** @return list<string> */
    public function allowedNextStatuses(string $from): array
    {
        return self::TRANSITIONS[self::normalize($from)] ?? [];
    }

    private static function normalize(string $status): string
    {
        return strtolower(trim($status));
    }
}
