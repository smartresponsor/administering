<?php

declare(strict_types=1);

namespace App\Administering\Entity;

use App\Administering\Repository\AdministrationConfigSnapshotRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdministrationConfigSnapshotRepository::class)]
#[ORM\Table(name: 'administration_config_snapshot')]
#[ORM\Index(name: 'idx_administration_config_snapshot_source', columns: ['source_type', 'source_path'])]
final class AdministrationConfigSnapshot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    private string $sourceType;

    #[ORM\Column(length: 255)]
    private string $sourcePath;

    #[ORM\Column(length: 190, nullable: true)]
    private ?string $componentName;

    #[ORM\Column(length: 64)]
    private string $checksum;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON)]
    private array $normalizedEntries = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $scannedAt;

    /** @param array<string, mixed> $normalizedEntries */
    public function __construct(string $sourceType, string $sourcePath, string $checksum, array $normalizedEntries = [], ?string $componentName = null, ?int $id = null)
    {
        $this->id = $id;
        $this->sourceType = $sourceType;
        $this->sourcePath = $sourcePath;
        $this->checksum = $checksum;
        $this->normalizedEntries = $normalizedEntries;
        $this->componentName = $componentName;
        $this->scannedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSourceType(): string
    {
        return $this->sourceType;
    }

    public function getSourcePath(): string
    {
        return $this->sourcePath;
    }

    public function getComponentName(): ?string
    {
        return $this->componentName;
    }

    public function getChecksum(): string
    {
        return $this->checksum;
    }

    /** @return array<string, mixed> */
    public function getNormalizedEntries(): array
    {
        return $this->normalizedEntries;
    }

    public function getScannedAt(): \DateTimeImmutable
    {
        return $this->scannedAt;
    }
}
