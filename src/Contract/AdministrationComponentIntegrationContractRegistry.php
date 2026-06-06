<?php

declare(strict_types=1);

namespace App\Administering\Contract;

/**
 * Administering's discovery registry for component integration contracts.
 *
 * This class knows WHICH components exist. It knows NOTHING about what
 * any contract contains — those are owned by each component.
 *
 * Wired via tagged_iterator 'administering.component_integration_contract'.
 *
 * Usage in a consumer service:
 *
 *   // Any of the 20+ consumer components can inject this registry.
 *   public function __construct(
 *       private readonly AdministrationComponentIntegrationContractRegistry $contracts,
 *   ) {}
 *
 *   public function someMethod(): void
 *   {
 *       // Safe: throws clearly if the requested component contract is not registered.
 *       $contract = $this->contracts->get('cataloging');
 *   }
 *
 * Adding a new component requires ZERO changes to this class
 */
final class AdministrationComponentIntegrationContractRegistry
{
    /** @var array<string, AdministrationComponentIntegrationContractInterface>|null lazy index */
    private ?array $index = null;

    /**
     * @param iterable<AdministrationComponentIntegrationContractInterface> $providers
     */
    public function __construct(
        private readonly iterable $providers,
    ) {
    }

    /**
     * Returns the typed contract object for the given component key.
     *
     * @throws \RuntimeException when the component has not registered a contract
     */
    public function get(string $componentKey): object
    {
        $provider = $this->index()[$componentKey] ?? null;

        if (null === $provider) {
            throw new \RuntimeException(\sprintf('No integration contract registered for component "%s". Registered: [%s]. Implement %s and tag it "administering.component_integration_contract".', $componentKey, implode(', ', $this->keys()), AdministrationComponentIntegrationContractInterface::class));
        }

        return $provider->contract();
    }

    public function has(string $componentKey): bool
    {
        return isset($this->index()[$componentKey]);
    }

    /**
     * All registered contract objects keyed by component key.
     *
     * @return array<string, object>
     */
    public function all(): array
    {
        return array_map(
            static fn (AdministrationComponentIntegrationContractInterface $p) => $p->contract(),
            $this->index(),
        );
    }

    /** @return string[] */
    public function keys(): array
    {
        return array_keys($this->index());
    }

    /** @return array<string, AdministrationComponentIntegrationContractInterface> */
    private function index(): array
    {
        if (null !== $this->index) {
            return $this->index;
        }

        $this->index = [];

        foreach ($this->providers as $provider) {
            $key = $provider->componentKey();

            if (isset($this->index[$key])) {
                throw new \LogicException(\sprintf('Duplicate integration contract for component "%s": both "%s" and "%s" registered.', $key, $this->index[$key]::class, $provider::class));
            }

            $this->index[$key] = $provider;
        }

        return $this->index;
    }
}
