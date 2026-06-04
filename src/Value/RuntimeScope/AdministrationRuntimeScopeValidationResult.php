<?php

declare(strict_types=1);

namespace App\Administering\Value\RuntimeScope;

final readonly class AdministrationRuntimeScopeValidationResult
{
    /**
     * @param list<string> $errors
     * @param list<string> $warnings
     */
    public function __construct(
        public string $hostDir,
        public string $environment,
        public string $lockFile,
        public string $composerFile,
        public int $enabledBundleCount,
        public int $disabledComponentCount,
        public array $errors,
        public array $warnings,
    ) {
    }

    public function isValid(): bool
    {
        return [] === $this->errors;
    }

    /** @return array{validation: string, hostDir: string, environment: string, lockFile: string, composerFile: string, enabledBundleCount: int, disabledComponentCount: int, errors: list<string>, warnings: list<string>} */
    public function toArray(): array
    {
        return [
            'validation' => 'administration_runtime_scope_validate',
            'hostDir' => $this->hostDir,
            'environment' => $this->environment,
            'lockFile' => $this->lockFile,
            'composerFile' => $this->composerFile,
            'enabledBundleCount' => $this->enabledBundleCount,
            'disabledComponentCount' => $this->disabledComponentCount,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
        ];
    }
}
