<?php

declare(strict_types=1);

namespace App\Administering\Value\RuntimeScope;

final readonly class AdministrationRuntimeScopeKernelRecipeRequest
{
    public function __construct(
        public string $hostDir,
        public bool $apply,
        public bool $force,
        public bool $patchKernel,
    ) {
    }
}
