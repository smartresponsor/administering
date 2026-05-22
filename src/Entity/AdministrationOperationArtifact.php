<?php

declare(strict_types=1);

namespace App\Administering\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Metadata-only pointer to a generated Administering operation artifact.
 *
 * Artifacts may reference safe manifests, summaries, diff previews, or patch
 * files. They must never contain plain secrets, raw .env values, decrypted
 * credentials, session payloads, password hashes, or 2FA internals.
 */
#[ORM\Entity]
#[ORM\Table(name: 'administration_operation_artifact')]
#[ORM\Index(name: 'idx_administration_operation_artifact_run', columns: ['operation_key'])]
#[ORM\Index(name: 'idx_administration_operation_artifact_type', columns: ['artifact_type'])]
final class AdministrationOperationArtifact
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'operation_key', type: 'string', length: 180)]
    private string $operationKey;

    #[ORM\Column(name: 'artifact_type', type: 'string', length: 80)]
    private string $artifactType;

    #[ORM\Column(name: 'safe_label', type: 'string', length: 180)]
    private string $safeLabel;

    #[ORM\Column(name: 'relative_path', type: 'string', length: 500)]
    private string $relativePath;

    #[ORM\Column(name: 'checksum', type: 'string', length: 128)]
    private string $checksum;

    /** @var array<string, mixed> */
    #[ORM\Column(name: 'safe_context', type: Types::JSON)]
    private array $safeContext = [];

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    /** @param array<string, mixed> $safeContext */
    public function __construct(
        string $operationKey,
        string $artifactType,
        string $safeLabel,
        string $relativePath,
        string $checksum,
        array $safeContext = [],
    ) {
        $this->operationKey = $operationKey;
        $this->artifactType = $artifactType;
        $this->safeLabel = $safeLabel;
        $this->relativePath = $relativePath;
        $this->checksum = $checksum;
        $this->safeContext = $safeContext;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function operationKey(): string
    {
        return $this->operationKey;
    }

    public function artifactType(): string
    {
        return $this->artifactType;
    }

    public function safeLabel(): string
    {
        return $this->safeLabel;
    }

    public function relativePath(): string
    {
        return $this->relativePath;
    }

    public function checksum(): string
    {
        return $this->checksum;
    }

    /** @return array<string, mixed> */
    public function safeContext(): array
    {
        return $this->safeContext;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getId(): ?int
    {
        return $this->id();
    }

    public function getOperationKey(): string
    {
        return $this->operationKey();
    }

    public function getArtifactType(): string
    {
        return $this->artifactType();
    }

    public function getSafeLabel(): string
    {
        return $this->safeLabel();
    }

    public function getRelativePath(): string
    {
        return $this->relativePath();
    }

    public function getChecksum(): string
    {
        return $this->checksum();
    }

    /** @return array<string, mixed> */
    public function getSafeContext(): array
    {
        return $this->safeContext();
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt();
    }
}
