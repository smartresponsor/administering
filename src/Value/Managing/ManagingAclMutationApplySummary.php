<?php

declare(strict_types=1);

namespace App\Administering\Value\Managing;

final readonly class ManagingAclMutationApplySummary
{
    /**
     * @param array<string, int> $countByStatus
     * @param array<string, int> $countByMutationType
     */
    public function __construct(
        public int $total,
        public int $succeeded,
        public int $failed,
        public array $countByStatus,
        public array $countByMutationType,
        public ?\DateTimeInterface $latestAt,
    ) {
    }

    /** @return array<string, mixed> */
    public function toSafeArray(): array
    {
        return [
            'total' => $this->total,
            'succeeded' => $this->succeeded,
            'failed' => $this->failed,
            'count_by_status' => $this->countByStatus,
            'count_by_mutation_type' => $this->countByMutationType,
            'latest_at' => $this->latestAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
