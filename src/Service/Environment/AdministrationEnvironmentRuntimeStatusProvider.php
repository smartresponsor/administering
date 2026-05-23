<?php

declare(strict_types=1);

namespace App\Administering\Service\Environment;

use App\Administering\ServiceInterface\Environment\AdministrationEnvironmentRuntimeStatusProviderInterface;

/**
 * Provides non-secret runtime environment metadata for the Environment section.
 */
final readonly class AdministrationEnvironmentRuntimeStatusProvider implements AdministrationEnvironmentRuntimeStatusProviderInterface
{
    public function __construct(
        private string $environment,
        private bool $debug,
    ) {
    }

    public function status(): array
    {
        return [
            'environment' => $this->environment,
            'debug' => $this->debug,
            'phpVersion' => PHP_VERSION,
        ];
    }
}
