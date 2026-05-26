<?php

declare(strict_types=1);

namespace App\Administering\Entity\Config;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'administration_config_value')]
#[ORM\Index(name: 'idx_administration_config_value_tool', columns: ['application_code', 'tool_code'])]
#[ORM\UniqueConstraint(name: 'uniq_administration_config_value_field', columns: ['application_code', 'tool_code', 'field_key'])]
final class AdministrationConfigValue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'application_code', length: 120)]
    private string $applicationCode;

    #[ORM\Column(name: 'tool_code', length: 160)]
    private string $toolCode;

    #[ORM\Column(name: 'field_key', length: 180)]
    private string $fieldKey;

    #[ORM\Column(name: 'field_type', length: 60)]
    private string $fieldType;

    #[ORM\Column(name: 'secret', type: 'boolean')]
    private bool $secret = false;

    #[ORM\Column(name: 'current_value', type: Types::TEXT, nullable: true)]
    private ?string $currentValue = null;

    #[ORM\Column(name: 'pending_value', type: Types::TEXT, nullable: true)]
    private ?string $pendingValue = null;

    #[ORM\Column(name: 'masked_value', length: 255, nullable: true)]
    private ?string $maskedValue = null;

    #[ORM\Column(length: 40)]
    private string $status = 'pending';

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct(string $applicationCode, string $toolCode, string $fieldKey, string $fieldType, bool $secret = false)
    {
        $this->applicationCode = $applicationCode;
        $this->toolCode = $toolCode;
        $this->fieldKey = $fieldKey;
        $this->fieldType = $fieldType;
        $this->secret = $secret;
        $this->updatedAt = new \DateTimeImmutable();
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

    public function getFieldKey(): string
    {
        return $this->fieldKey;
    }

    public function getFieldType(): string
    {
        return $this->fieldType;
    }

    public function isSecret(): bool
    {
        return $this->secret;
    }

    public function getCurrentValue(): ?string
    {
        return $this->currentValue;
    }

    public function getPendingValue(): ?string
    {
        return $this->pendingValue;
    }

    public function getMaskedValue(): ?string
    {
        return $this->maskedValue;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function markCurrent(?string $currentValue, ?string $pendingValue, ?string $maskedValue, string $status): void
    {
        $this->currentValue = $currentValue;
        $this->pendingValue = $pendingValue;
        $this->maskedValue = $maskedValue;
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();
    }
}
