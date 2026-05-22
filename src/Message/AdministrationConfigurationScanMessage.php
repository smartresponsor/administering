<?php

declare(strict_types=1);

namespace App\Administering\Message;

final readonly class AdministrationConfigurationScanMessage
{
    public function __construct(private string $hostRoot)
    {
    }

    public function hostRoot(): string
    {
        return $this->hostRoot;
    }
}
