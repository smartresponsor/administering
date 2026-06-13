<?php

declare(strict_types=1);

namespace App\Administering\Entity\Config;

use App\Administering\Repository\Config\AdministrationConfigApplyLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdministrationConfigApplyLogRepository::class)]
#[ORM\Table(name: 'administration_config_apply_log')]
#[ORM\Index(name: 'idx_administration_config_apply_log_tool', columns: ['application_code', 'tool_code'])]
#[ORM\Index(name: 'idx_administration_config_apply_log_status', columns: ['status'])]
final class AdministrationConfigApplyLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'application_code', length: 120)]
    private string $applicationCode;

    #[ORM\Column(name: 'tool_code', length: 160)]
    private string $toolCode;

    #[ORM\Column(name: 'actor_identifier', length: 180)]
    private string $actorIdentifier;

    #[ORM\Column(length: 40)]
    private string $status;

    /** @var array<string, mixed> */
    #[ORM\Column(name: 'changed_fields', type: Types::JSON)]
    private array $changedFields = [];

    /** @var array<string, mixed> */
    #[ORM\Column(name: 'masked_secrets', type: Types::JSON)]
    private array $maskedSecrets = [];

    #[ORM\Column(name: 'error_message', type: Types::TEXT, nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(name: 'applied_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $appliedAt;

    /**
     * @param array<string, mixed> $changedFields
     * @param array<string, mixed> $maskedSecrets
     */
    public function __construct(
        string $applicationCode,
        string $toolCode,
        string $actorIdentifier,
        string $status,
        array $changedFields = [],
        array $maskedSecrets = [],
        ?string $errorMessage = null,
        ?int $id = null,
    ) {
        $this->id = $id;
        $this->applicationCode = $applicationCode;
        $this->toolCode = $toolCode;
        $this->actorIdentifier = $actorIdentifier;
        $this->status = $status;
        $this->changedFields = $changedFields;
        $this->maskedSecrets = $maskedSecrets;
        $this->errorMessage = $errorMessage;
        $this->appliedAt = new \DateTimeImmutable();
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

    public function getActorIdentifier(): string
    {
        return $this->actorIdentifier;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    /** @return array<string, mixed> */
    public function getChangedFields(): array
    {
        return $this->changedFields;
    }

    /** @return array<string, mixed> */
    public function getMaskedSecrets(): array
    {
        return $this->maskedSecrets;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getAppliedAt(): \DateTimeImmutable
    {
        return $this->appliedAt;
    }
}
