<?php

declare(strict_types=1);

namespace App\Administering\Value\Admin;

/**
 * Read-only report describing which owner-provided tools are safe to materialize.
 */
final readonly class AdministrationOwnerConfigurationToolMaterializationReport
{
    /**
     * @param list<array<string, mixed>> $providers
     * @param list<array<string, mixed>> $acceptedTools
     * @param list<array<string, mixed>> $rejectedTools
     */
    public function __construct(
        public array $providers,
        public array $acceptedTools,
        public array $rejectedTools,
    ) {
    }

    public function providerCount(): int
    {
        return count($this->providers);
    }

    public function acceptedCount(): int
    {
        return count($this->acceptedTools);
    }

    public function rejectedCount(): int
    {
        return count($this->rejectedTools);
    }

    public function hasRejectedTools(): bool
    {
        return [] !== $this->rejectedTools;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema' => 'administering.owner_configuration_tool_materialization_preview.v1',
            'providers' => $this->providers,
            'summary' => [
                'providers' => $this->providerCount(),
                'acceptedTools' => $this->acceptedCount(),
                'rejectedTools' => $this->rejectedCount(),
            ],
            'acceptedTools' => $this->acceptedTools,
            'rejectedTools' => $this->rejectedTools,
        ];
    }
}
