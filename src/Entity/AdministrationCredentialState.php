<?php

declare(strict_types=1);

namespace App\Administering\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Stores credential state metadata only.
 *
 * Plain or decrypted secret values are forbidden here. Store only presence, status,
 * masked state, fingerprints, and audit-safe metadata.
 */
#[ORM\Entity]
#[ORM\Table(name: 'administration_credential_state')]
#[ORM\UniqueConstraint(name: 'uniq_administration_credential_state_key_env', columns: ['credential_key', 'environment_name'])]
class AdministrationCredentialState
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'credential_key', type: 'string', length: 180)]
    private string $credentialKey;

    #[ORM\Column(name: 'environment_name', type: 'string', length: 40)]
    private string $environmentName;

    #[ORM\Column(name: 'present', type: 'boolean')]
    private bool $present = false;

    #[ORM\Column(name: 'source_type', type: 'string', length: 40)]
    private string $sourceType = 'symfony_secret';

    #[ORM\Column(name: 'safe_fingerprint', type: 'string', length: 128, nullable: true)]
    private ?string $safeFingerprint = null;

    #[ORM\Column(name: 'status', type: 'string', length: 40)]
    private string $status = 'unknown';

    #[ORM\Column(name: 'checked_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $checkedAt = null;

    public function __construct(string $credentialKey, string $environmentName = 'prod')
    {
        $this->credentialKey = $credentialKey;
        $this->environmentName = $environmentName;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function credentialKey(): string
    {
        return $this->credentialKey;
    }

    public function environmentName(): string
    {
        return $this->environmentName;
    }

    public function present(): bool
    {
        return $this->present;
    }

    public function sourceType(): string
    {
        return $this->sourceType;
    }

    public function safeFingerprint(): ?string
    {
        return $this->safeFingerprint;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function checkedAt(): ?\DateTimeImmutable
    {
        return $this->checkedAt;
    }

    public function markChecked(bool $present, string $status, ?string $safeFingerprint = null): void
    {
        $this->present = $present;
        $this->status = $status;
        $this->safeFingerprint = $safeFingerprint;
        $this->checkedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id();
    }

    public function getCredentialKey(): string
    {
        return $this->credentialKey();
    }

    public function getEnvironmentName(): string
    {
        return $this->environmentName();
    }

    public function isPresent(): bool
    {
        return $this->present();
    }

    public function getSourceType(): string
    {
        return $this->sourceType();
    }

    public function getSafeFingerprint(): ?string
    {
        return $this->safeFingerprint();
    }

    public function getStatus(): string
    {
        return $this->status();
    }

    public function getCheckedAt(): ?\DateTimeImmutable
    {
        return $this->checkedAt();
    }
}
