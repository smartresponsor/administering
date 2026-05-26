<?php

declare(strict_types=1);

namespace App\Administering\Service\Config;

use App\Administering\ServiceInterface\Config\AdministrationConfigToolServiceInterface;

final readonly class ConfigToolServiceLocator
{
    /**
     * @param iterable<AdministrationConfigToolServiceInterface> $toolServices
     */
    public function __construct(private iterable $toolServices = [])
    {
    }

    public function forTool(string $applicationCode, string $toolCode): ?AdministrationConfigToolServiceInterface
    {
        foreach ($this->toolServices as $service) {
            $descriptor = $service->descriptor();
            if ($descriptor->applicationCode === $applicationCode && $descriptor->toolCode === $toolCode) {
                return $service;
            }
        }

        return null;
    }
}
