<?php

declare(strict_types=1);

namespace App\Administering\Factory\RuntimeScope;

final readonly class AdministrationRuntimeScopePhpLockSourceFactory
{
    /** @param array<string, mixed> $payload */
    public function source(array $payload): string
    {
        return "<?php\n\ndeclare(strict_types=1);\n\nreturn ".var_export($payload, true).";\n";
    }
}
