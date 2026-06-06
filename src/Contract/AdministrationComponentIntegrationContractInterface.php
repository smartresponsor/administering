<?php

declare(strict_types=1);

namespace App\Administering\Contract;

/**
 * Implemented by each component that wants to expose its typed integration
 * contract through the AdministrationComponentIntegrationContractRegistry.
 *
 * Administering knows ONLY this interface — it never inspects the concrete
 * contract object returned by contract().
 *
 * ┌──────────────────────────────────────────────────────────────────┐
 * │  Pattern for a new component (e.g. Billing):                    │
 * │                                                                  │
 * │  // Billing/src/Contract/BillingIntegrationContract.php          │
 * │  final readonly class BillingIntegrationContract                 │
 * │  {                                                               │
 * │      public function __construct(                                │
 * │          public string $stripePublicKey,                         │
 * │          public string $webhookHandlerService,                   │
 * │          // ... whatever Billing owns                            │
 * │      ) {}                                                        │
 * │  }                                                               │
 * │                                                                  │
 * │  // Administering/src/Contract/Billing/                          │
 * │  //   BillingComponentIntegrationContractProvider.php            │
 * │  final class BillingComponentIntegrationContractProvider         │
 * │      implements AdministrationComponentIntegrationContractInterface            │
 * │  {                                                               │
 * │      public function componentKey(): string { return 'billing'; }│
 * │      public function contract(): BillingIntegrationContract      │
 * │      { return $this->billingProvider->integrationContract(); }   │
 * │  }                                                               │
 * └──────────────────────────────────────────────────────────────────┘
 *
 * Tag: administering.component_integration_contract
 */
interface AdministrationComponentIntegrationContractInterface
{
    /**
     * Lowercase snake key identifying this component.
     * Examples: 'connected', 'billing', 'cataloging'.
     */
    public function componentKey(): string;

    /**
     * Returns the component's typed contract value object.
     *
     * The return type is intentionally 'object' here — Administering is a
     * generic registry and must not be coupled to each component's DTO.
     * Consumers that need a typed contract cast after get():
     *
     *   $contract = $registry->get('cataloging');
     */
    public function contract(): object;
}
