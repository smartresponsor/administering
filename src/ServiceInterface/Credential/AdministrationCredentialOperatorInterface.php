<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Credential;

use App\Administering\Value\Credential\AdministrationCredentialOperationResult;

interface AdministrationCredentialOperatorInterface
{
    public function list(string $environment): AdministrationCredentialOperationResult;

    public function set(string $environment, string $credentialKey, string $plainValue): AdministrationCredentialOperationResult;

    public function remove(string $environment, string $credentialKey): AdministrationCredentialOperationResult;
}
