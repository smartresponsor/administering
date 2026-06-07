<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Tool;

use App\Administering\Value\Tool\ConfigurationToolDefinition;

interface ConfigurationToolProviderInterface
{
    public function componentKey(): string;

    public function componentToken(): string;

    /** @return iterable<ConfigurationToolDefinition> */
    public function tools(): iterable;
}
