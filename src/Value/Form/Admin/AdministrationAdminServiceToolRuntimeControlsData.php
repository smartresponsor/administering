<?php

declare(strict_types=1);

namespace App\Administering\Value\Form\Admin;

use App\Administering\Entity\AdministrationServiceToolRecord;

final class AdministrationAdminServiceToolRuntimeControlsData
{
    public function __construct(
        public bool $enabled = true,
        public bool $visible = true,
        public int $position = 100,
        public ?string $labelOverride = null,
        public bool $clearLabelOverride = false,
    ) {
    }

    public static function fromRecord(AdministrationServiceToolRecord $record): self
    {
        return new self(
            enabled: $record->isEnabled(),
            visible: $record->isVisible(),
            position: $record->getPosition(),
            labelOverride: $record->getLabelOverride(),
            clearLabelOverride: false,
        );
    }
}
