<?php

declare(strict_types=1);

namespace App\Administering\Value\Admin;

/**
 * Result returned by one service-section anchor synchronization tool.
 */
final readonly class AdministrationServiceSectionAnchorSyncResult
{
    /** @param list<string> $messages */
    public function __construct(
        public string $sectionKey,
        public int $recordCount,
        public string $status = 'synced',
        public array $messages = [],
    ) {
    }

    /** @return array{sectionKey: string, recordCount: int, status: string, messages: list<string>} */
    public function toArray(): array
    {
        return [
            'sectionKey' => $this->sectionKey,
            'recordCount' => $this->recordCount,
            'status' => $this->status,
            'messages' => $this->messages,
        ];
    }
}
