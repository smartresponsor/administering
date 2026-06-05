<?php

declare(strict_types=1);

namespace App\Administering\Recorder\Audit;

use App\Administering\ServiceInterface\Audit\AdministrationAuditRecorderInterface;

final class AdministrationNullAuditRecorder implements AdministrationAuditRecorderInterface
{
    public function record(string $action, string $subjectIdentifier, array $context = []): void
    {
    }
}
