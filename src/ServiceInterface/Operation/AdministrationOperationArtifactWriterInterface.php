<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Operation;

use App\Administering\Entity\AdministrationOperationArtifact;

/**
 * Writes safe operation artifacts and persists metadata-only pointers.
 */
interface AdministrationOperationArtifactWriterInterface
{
    /** @param array<string, mixed> $safePayload */
    public function writeJsonArtifact(string $operationKey, string $artifactType, string $safeLabel, array $safePayload): AdministrationOperationArtifact;
}
