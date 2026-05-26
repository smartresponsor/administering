<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Admin;

use App\Administering\Value\Admin\AdministrationOwnerConfigurationToolDefinition;

/**
 * Implemented by owner components that expose their own configuration tools.
 *
 * Administering discovers tagged providers, materializes their tools into the
 * SQLite service-tool index, and renders the owner-provided forms. The owner
 * component remains responsible for the tool prefix, form data, validation, and
 * executable handler semantics.
 */
interface AdministrationOwnerConfigurationToolProviderInterface
{
    public function componentKey(): string;

    public function componentToken(): string;

    /** @return iterable<AdministrationOwnerConfigurationToolDefinition> */
    public function tools(): iterable;
}
