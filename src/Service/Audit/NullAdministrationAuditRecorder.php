<?php

declare(strict_types=1);

namespace App\Administering\Service\Audit;

use App\Administering\ServiceInterface\Audit\AdministrationAuditRecorderInterface;

final class NullAdministrationAuditRecorder implements AdministrationAuditRecorderInterface
{
    public function record(string $action, string $subjectIdentifier, array $context = []): void
    {
    }
}
