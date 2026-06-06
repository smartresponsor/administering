<?php

declare(strict_types=1);

namespace App\Administering\Value\Form\RuntimeScope;

use App\Administering\Entity\AdministrationConnectedComponentRecord;

final class AdministrationRuntimeScopeComponentDecisionData
{
    public function __construct(
        public string $componentKey = '',
        public string $environment = 'dev',
        public bool $enabled = true,
        public ?string $reason = null,
    ) {
    }

    public static function fromRecord(AdministrationConnectedComponentRecord $record, string $environment): self
    {
        return new self(
            componentKey: $record->getComponentName(),
            environment: $environment,
            enabled: 'prod' === $environment ? $record->isEnabledForProd() : $record->isEnabledForDev(),
            reason: null,
        );
    }
}
