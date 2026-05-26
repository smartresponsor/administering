<?php

declare(strict_types=1);

namespace App\Administering\Value\Admin;

/**
 * Read-only report deciding whether available owner/host current slices can move
 * from intake to concrete repository-specific patch waves.
 */
final readonly class AdministrationOwnerRepositoryPatchReadinessReport
{
    /**
     * @param list<array{name:string, path:string, status:string}>                                                                                                                                                 $artifactChecks
     * @param list<array{componentKey:string, repositoryName:string, expectedPath:string, sliceStatus:string, patchMode:string, readyForConcretePatch:bool, blockingReason:?string, recommendedNextAction:string}> $repositoryReadiness
     */
    public function __construct(
        public array $artifactChecks,
        public array $repositoryReadiness,
        public bool $readyForPatchWaves,
        public string $nextWorkMode,
    ) {
    }

    public function readyRepositoryCount(): int
    {
        return count(array_filter($this->repositoryReadiness, static fn (array $item): bool => true === ($item['readyForConcretePatch'] ?? false)));
    }

    public function blockedRepositoryCount(): int
    {
        return count($this->repositoryReadiness) - $this->readyRepositoryCount();
    }

    public function missingArtifactCount(): int
    {
        return count(array_filter($this->artifactChecks, static fn (array $item): bool => 'present' !== ($item['status'] ?? null)));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema' => 'smart-responsor.administering.owner_repository_patch_readiness.v1',
            'generatedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'readyForPatchWaves' => $this->readyForPatchWaves,
            'nextWorkMode' => $this->nextWorkMode,
            'readyRepositoryCount' => $this->readyRepositoryCount(),
            'blockedRepositoryCount' => $this->blockedRepositoryCount(),
            'missingArtifactCount' => $this->missingArtifactCount(),
            'artifactChecks' => $this->artifactChecks,
            'repositoryReadiness' => $this->repositoryReadiness,
        ];
    }
}
