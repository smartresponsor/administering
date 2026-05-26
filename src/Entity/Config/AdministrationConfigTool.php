<?php

declare(strict_types=1);

namespace App\Administering\Entity\Config;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'administration_config_tool')]
#[ORM\Index(name: 'idx_administration_config_tool_application', columns: ['application_code'])]
#[ORM\Index(name: 'idx_administration_config_tool_code', columns: ['tool_code'])]
#[ORM\UniqueConstraint(name: 'uniq_administration_config_tool_application_tool', columns: ['application_code', 'tool_code'])]
final class AdministrationConfigTool
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'application_code', length: 120)]
    private string $applicationCode;

    #[ORM\Column(name: 'tool_code', length: 160)]
    private string $toolCode;

    #[ORM\Column(length: 180)]
    private string $label;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'form_class', length: 255)]
    private string $formClass;

    #[ORM\Column(name: 'service_class', length: 255)]
    private string $serviceClass;

    #[ORM\Column(name: 'required_permission', length: 180)]
    private string $requiredPermission;

    #[ORM\Column(name: 'apply_strategy', length: 64)]
    private string $applyStrategy = 'component_yaml';

    #[ORM\Column(length: 40)]
    private string $status = 'discovered';

    /** @var array<int, string> */
    #[ORM\Column(name: 'editable_fields', type: Types::JSON)]
    private array $editableFields = [];

    /** @var array<int, string> */
    #[ORM\Column(name: 'sensitive_fields', type: Types::JSON)]
    private array $sensitiveFields = [];

    /** @var array<int, string> */
    #[ORM\Column(name: 'readable_files', type: Types::JSON)]
    private array $readableFiles = [];

    /** @var array<int, string> */
    #[ORM\Column(name: 'writable_files', type: Types::JSON)]
    private array $writableFiles = [];

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON)]
    private array $metadata = [];

    /** @var array<string, string> */
    #[ORM\Column(name: 'secret_names', type: Types::JSON)]
    private array $secretNames = [];

    #[ORM\Column(name: 'discovered_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $discoveredAt;

    /**
     * @param list<string>          $editableFields
     * @param list<string>          $sensitiveFields
     * @param list<string>          $readableFiles
     * @param list<string>          $writableFiles
     * @param array<string, mixed>  $metadata
     * @param array<string, string> $secretNames
     */
    public function __construct(
        string $applicationCode,
        string $toolCode,
        string $label,
        string $formClass,
        string $serviceClass,
        string $requiredPermission,
        array $editableFields = [],
        array $sensitiveFields = [],
        array $readableFiles = [],
        array $writableFiles = [],
        array $metadata = [],
        array $secretNames = [],
        ?string $description = null,
        string $applyStrategy = 'component_yaml',
    ) {
        $this->applicationCode = $applicationCode;
        $this->toolCode = $toolCode;
        $this->label = $label;
        $this->formClass = $formClass;
        $this->serviceClass = $serviceClass;
        $this->requiredPermission = $requiredPermission;
        $this->editableFields = array_values($editableFields);
        $this->sensitiveFields = array_values($sensitiveFields);
        $this->readableFiles = array_values($readableFiles);
        $this->writableFiles = array_values($writableFiles);
        $this->metadata = $metadata;
        $this->secretNames = $secretNames;
        $this->description = $description;
        $this->applyStrategy = $applyStrategy;
        $this->discoveredAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApplicationCode(): string
    {
        return $this->applicationCode;
    }

    public function getToolCode(): string
    {
        return $this->toolCode;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getFormClass(): string
    {
        return $this->formClass;
    }

    public function getServiceClass(): string
    {
        return $this->serviceClass;
    }

    public function getRequiredPermission(): string
    {
        return $this->requiredPermission;
    }

    public function getApplyStrategy(): string
    {
        return $this->applyStrategy;
    }

    /** @return list<string> */
    public function getEditableFields(): array
    {
        return $this->editableFields;
    }

    /** @return list<string> */
    public function getSensitiveFields(): array
    {
        return $this->sensitiveFields;
    }

    /** @return list<string> */
    public function getReadableFiles(): array
    {
        return $this->readableFiles;
    }

    /** @return list<string> */
    public function getWritableFiles(): array
    {
        return $this->writableFiles;
    }

    /** @return array<string, mixed> */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /** @return array<string, string> */
    public function getSecretNames(): array
    {
        return $this->secretNames;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getDiscoveredAt(): \DateTimeImmutable
    {
        return $this->discoveredAt;
    }
}
