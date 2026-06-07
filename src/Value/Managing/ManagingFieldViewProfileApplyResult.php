<?php

declare(strict_types=1);

namespace App\Administering\Value\Managing;

final readonly class ManagingFieldViewProfileApplyResult
{
    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $safeContext
     */
    public function __construct(
        public bool $valid,
        public string $status,
        public string $message,
        public array $payload,
        public array $safeContext = [],
    ) {
    }

    /** @return array<string, mixed> */
    public function toSafeArray(): array
    {
        return [
            'valid' => $this->valid,
            'status' => $this->status,
            'message' => $this->message,
            'payload' => $this->payload,
            'safe_context' => $this->safeContext,
        ];
    }
}
