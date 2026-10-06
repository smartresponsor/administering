<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Tool;

use App\Administering\Value\Tool\AdministrationConfigurationToolDefinition;

interface AdministrationConfigurationToolProviderInterface
{
    public function componentKey(): string;

    public function componentToken(): string;

    /** @return iterable<AdministrationConfigurationToolDefinition> */
    public function tools(): iterable;
}
