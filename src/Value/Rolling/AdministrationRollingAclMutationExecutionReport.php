<?php

declare(strict_types=1);

namespace App\Administering\Value\Rolling;

final readonly class AdministrationRollingAclMutationExecutionReport
{
    /**
     * @param list<array<string, mixed>> $records
     * @param array<string, mixed>       $filters
     */
    public function __construct(private array $records, private array $filters)
    {
    }

    /** @return array<string, mixed> */
    public function toSafeArray(): array
    {
        return ['records' => $this->records, 'filters' => $this->filters, 'count' => count($this->records)];
    }
}
