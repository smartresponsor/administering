<?php

declare(strict_types=1);

namespace App\Administering\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'administration_service_tool_record')]
#[ORM\Index(name: 'idx_administration_service_tool_section', columns: ['section_key'])]
#[ORM\Index(name: 'idx_administration_service_tool_key', columns: ['tool_key'])]
#[ORM\Index(name: 'idx_administration_service_tool_class', columns: ['service_class'])]
#[ORM\Index(name: 'idx_administration_service_tool_status', columns: ['status'])]
#[ORM\Index(name: 'idx_administration_service_tool_source_ownership', columns: ['source_ownership'])]
#[ORM\Index(name: 'idx_administration_service_tool_owner_component', columns: ['owner_component_key'])]
final class AdministrationServiceToolRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    private string $sectionKey;

    #[ORM\Column(length: 120)]
    private string $directionToken;

    #[ORM\Column(length: 180)]
    private string $toolSlug;

    #[ORM\Column(length: 220)]
    private string $toolKey;

    #[ORM\Column(length: 180)]
    private string $label;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $labelOverride = null;

    #[ORM\Column(length: 255)]
    private string $serviceClass;

    #[ORM\Column(length: 180)]
    private string $serviceShortName;

    #[ORM\Column(length: 255)]
    private string $serviceFile;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $formTypeClass;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $formDataClass;

    #[ORM\Column(length: 120)]
    private string $operationType;

    #[ORM\Column]
    private bool $executable;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $primaryRouteName;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $primaryRouteLabel;

    #[ORM\Column(length: 40)]
    private string $sourceOwnership;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $ownerComponentKey = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $ownerComponentToken = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ownerProviderClass = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ownerServiceClass = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $ownerSourceLabel = null;

    #[ORM\Column(length: 40)]
    private string $status;

    #[ORM\Column]
    private bool $enabled;

    #[ORM\Column]
    private bool $visible;

    #[ORM\Column]
    private int $position;

    #[ORM\Column(length: 64)]
    private string $checksum;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON)]
    private array $safeContext = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $synchronizedAt;

    /** @param array<string, mixed> $safeContext */
    public function __construct(
        string $sectionKey,
        string $directionToken,
        string $toolSlug,
        string $toolKey,
        string $label,
        string $serviceClass,
        string $serviceShortName,
        string $serviceFile,
        ?string $formTypeClass,
        ?string $formDataClass,
        string $operationType,
        bool $executable,
        ?string $primaryRouteName,
        ?string $primaryRouteLabel,
        string $sourceOwnership,
        ?string $ownerComponentKey,
        ?string $ownerComponentToken,
        ?string $ownerProviderClass,
        ?string $ownerServiceClass,
        ?string $ownerSourceLabel,
        string $status,
        bool $enabled,
        bool $visible,
        int $position,
        string $checksum,
        array $safeContext = [],
        ?int $id = null,
    ) {
        $this->id = $id;
        $this->sectionKey = $sectionKey;
        $this->directionToken = $directionToken;
        $this->toolSlug = $toolSlug;
        $this->toolKey = $toolKey;
        $this->label = $label;
        $this->serviceClass = $serviceClass;
        $this->serviceShortName = $serviceShortName;
        $this->serviceFile = $serviceFile;
        $this->formTypeClass = $formTypeClass;
        $this->formDataClass = $formDataClass;
        $this->operationType = $operationType;
        $this->executable = $executable;
        $this->primaryRouteName = $primaryRouteName;
        $this->primaryRouteLabel = $primaryRouteLabel;
        $this->sourceOwnership = $sourceOwnership;
        $this->ownerComponentKey = $ownerComponentKey;
        $this->ownerComponentToken = $ownerComponentToken;
        $this->ownerProviderClass = $ownerProviderClass;
        $this->ownerServiceClass = $ownerServiceClass;
        $this->ownerSourceLabel = $ownerSourceLabel;
        $this->status = $status;
        $this->enabled = $enabled;
        $this->visible = $visible;
        $this->position = $position;
        $this->checksum = $checksum;
        $this->safeContext = $safeContext;
        $this->synchronizedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSectionKey(): string
    {
        return $this->sectionKey;
    }

    public function getDirectionToken(): string
    {
        return $this->directionToken;
    }

    public function getToolSlug(): string
    {
        return $this->toolSlug;
    }

    public function getToolKey(): string
    {
        return $this->toolKey;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getGeneratedLabel(): string
    {
        return $this->label;
    }

    public function getLabelOverride(): ?string
    {
        return $this->labelOverride;
    }

    public function getDisplayLabel(): string
    {
        return is_string($this->labelOverride) && '' !== trim($this->labelOverride)
            ? trim($this->labelOverride)
            : $this->label;
    }

    public function getServiceClass(): string
    {
        return $this->serviceClass;
    }

    public function getServiceShortName(): string
    {
        return $this->serviceShortName;
    }

    public function getServiceFile(): string
    {
        return $this->serviceFile;
    }

    public function getFormTypeClass(): ?string
    {
        return $this->formTypeClass;
    }

    public function getFormDataClass(): ?string
    {
        return $this->formDataClass;
    }

    public function hasFormType(): bool
    {
        return is_string($this->formTypeClass) && '' !== $this->formTypeClass;
    }

    public function hasFormDataClass(): bool
    {
        return is_string($this->formDataClass) && '' !== $this->formDataClass;
    }

    public function getOperationType(): string
    {
        return $this->operationType;
    }

    public function isExecutable(): bool
    {
        return $this->executable;
    }

    public function isOpenable(): bool
    {
        return $this->enabled && $this->visible && $this->hasFormType();
    }

    public function isRunnable(): bool
    {
        return $this->isOpenable() && $this->executable;
    }

    public function getPrimaryRouteName(): ?string
    {
        return $this->primaryRouteName;
    }

    public function getPrimaryRouteLabel(): ?string
    {
        return $this->primaryRouteLabel;
    }

    public function getSourceOwnership(): string
    {
        return $this->sourceOwnership;
    }

    public function isOwnerProvided(): bool
    {
        return 'owner_component' === $this->sourceOwnership;
    }

    public function isAdministeringInternal(): bool
    {
        return 'administering_internal' === $this->sourceOwnership;
    }

    public function getOwnerComponentKey(): ?string
    {
        return $this->ownerComponentKey;
    }

    public function getOwnerComponentToken(): ?string
    {
        return $this->ownerComponentToken;
    }

    public function getOwnerProviderClass(): ?string
    {
        return $this->ownerProviderClass;
    }

    public function getOwnerServiceClass(): ?string
    {
        return $this->ownerServiceClass;
    }

    public function getOwnerSourceLabel(): ?string
    {
        return $this->ownerSourceLabel;
    }

    public function getSourceLabel(): string
    {
        return $this->ownerSourceLabel ?: ('owner_component' === $this->sourceOwnership ? 'Owner component' : 'Administering internal');
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function configureRuntimeControls(?bool $enabled, ?bool $visible, ?int $position, ?string $labelOverride = null, bool $clearLabelOverride = false): void
    {
        if (null !== $enabled) {
            $this->enabled = $enabled;
        }

        if (null !== $visible) {
            $this->visible = $visible;
        }

        if (null !== $position) {
            $this->position = $position;
        }

        if ($clearLabelOverride) {
            $this->labelOverride = null;
        } elseif (null !== $labelOverride) {
            $normalizedLabelOverride = trim($labelOverride);
            $this->labelOverride = '' === $normalizedLabelOverride ? null : $normalizedLabelOverride;
        }
    }

    public function getChecksum(): string
    {
        return $this->checksum;
    }

    /** @return array<string, mixed> */
    public function getSafeContext(): array
    {
        return $this->safeContext;
    }

    public function getSynchronizedAt(): \DateTimeImmutable
    {
        return $this->synchronizedAt;
    }
}
