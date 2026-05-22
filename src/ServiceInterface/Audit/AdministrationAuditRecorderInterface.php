<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Audit;

interface AdministrationAuditRecorderInterface
{
    /** @param array<string, mixed> $context */
    public function record(string $action, string $subjectIdentifier, array $context = []): void;
}
