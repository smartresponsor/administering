<?php

declare(strict_types=1);

namespace App\Administering\Value\Rolling;

final readonly class AdministrationRollingFieldAccessScopeSet
{
    public function __construct(private string $mostSpecificScope)
    {
    }

    public static function fromRequest(AdministrationRollingFieldAccessDecisionRequest $request): self
    {
        $parts = array_filter([
            $request->componentKey,
            str_replace('\\', '.', $request->resourceClass),
            $request->pageName,
            $request->fieldName,
            $request->operation,
        ], static fn (string $part): bool => '' !== trim($part));

        return new self(implode(':', $parts) ?: 'administering:global');
    }

    public function mostSpecificScope(): string
    {
        return $this->mostSpecificScope;
    }
}
