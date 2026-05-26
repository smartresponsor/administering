<?php

declare(strict_types=1);

namespace App\Administering\Value\Admin;

/**
 * Final read-only work order for moving from Administering internal transition
 * waves to concrete owner/host repository current-slice patch waves.
 */
final readonly class AdministrationOwnerRepositoryWorkOrderReport
{
    /**
     * @param list<array<string, mixed>> $repositoryWorkOrders
     * @param list<array<string, mixed>> $hostApplicationWorkOrders
     * @param list<array<string, mixed>> $administeringShellWorkOrders
     * @param list<array<string, mixed>> $artifactReferences
     * @param list<string>               $recommendedNextActions
     */
    public function __construct(
        public array $repositoryWorkOrders,
        public array $hostApplicationWorkOrders,
        public array $administeringShellWorkOrders,
        public array $artifactReferences,
        public array $recommendedNextActions,
        public string $nextWorkMode,
    ) {
    }

    public function ownerRepositoryCount(): int
    {
        return count($this->repositoryWorkOrders);
    }

    public function hostApplicationCount(): int
    {
        return count($this->hostApplicationWorkOrders);
    }

    public function administeringShellWorkCount(): int
    {
        return count($this->administeringShellWorkOrders);
    }

    public function missingArtifactCount(): int
    {
        return count(array_filter($this->artifactReferences, static fn (array $item): bool => 'present' !== ($item['status'] ?? null)));
    }

    public function canRequestOwnerSlices(): bool
    {
        return $this->ownerRepositoryCount() > 0;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema' => 'smart-responsor.administering.owner_repository_work_order.v1',
            'generatedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'nextWorkMode' => $this->nextWorkMode,
            'canRequestOwnerSlices' => $this->canRequestOwnerSlices(),
            'ownerRepositoryCount' => $this->ownerRepositoryCount(),
            'hostApplicationCount' => $this->hostApplicationCount(),
            'administeringShellWorkCount' => $this->administeringShellWorkCount(),
            'missingArtifactCount' => $this->missingArtifactCount(),
            'repositoryWorkOrders' => $this->repositoryWorkOrders,
            'hostApplicationWorkOrders' => $this->hostApplicationWorkOrders,
            'administeringShellWorkOrders' => $this->administeringShellWorkOrders,
            'artifactReferences' => $this->artifactReferences,
            'recommendedNextActions' => $this->recommendedNextActions,
        ];
    }
}
