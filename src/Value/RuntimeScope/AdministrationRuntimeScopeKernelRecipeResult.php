<?php

declare(strict_types=1);

namespace App\Administering\Value\RuntimeScope;

final readonly class AdministrationRuntimeScopeKernelRecipeResult
{
    /**
     * @param array<string, mixed>       $composerInventory
     * @param list<array<string, mixed>> $actions
     * @param list<string>               $errors
     * @param list<string>               $nextActions
     */
    public function __construct(
        public string $recipe,
        public string $hostDir,
        public bool $apply,
        public bool $force,
        public bool $kernelPatchRequested,
        public array $composerInventory,
        public array $actions,
        public array $errors,
        public array $nextActions,
    ) {
    }

    public function isSuccessful(): bool
    {
        return [] === $this->errors;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'recipe' => $this->recipe,
            'hostDir' => $this->hostDir,
            'apply' => $this->apply,
            'force' => $this->force,
            'kernelPatchRequested' => $this->kernelPatchRequested,
            'composerInventory' => $this->composerInventory,
            'actions' => $this->actions,
            'errors' => $this->errors,
            'nextActions' => $this->nextActions,
        ];
    }
}
