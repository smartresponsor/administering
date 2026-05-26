<?php

declare(strict_types=1);

namespace App\Administering\Entity\Config;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'administration_config_application')]
#[ORM\UniqueConstraint(name: 'uniq_administration_config_application_code', columns: ['application_code'])]
final class AdministrationConfigApplication
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'application_code', length: 120)]
    private string $applicationCode;

    #[ORM\Column(length: 180)]
    private string $label;

    #[ORM\Column(name: 'root_path', length: 255)]
    private string $rootPath;

    #[ORM\Column(name: 'manifest_path', length: 255)]
    private string $manifestPath;

    #[ORM\Column(length: 40)]
    private string $status = 'discovered';

    #[ORM\Column]
    private bool $enabled = true;

    #[ORM\Column(length: 64)]
    private string $checksum;

    #[ORM\Column(name: 'discovered_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $discoveredAt;

    public function __construct(string $applicationCode, string $label, string $rootPath, string $manifestPath, string $checksum)
    {
        $this->applicationCode = $applicationCode;
        $this->label = $label;
        $this->rootPath = $rootPath;
        $this->manifestPath = $manifestPath;
        $this->checksum = $checksum;
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

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getRootPath(): string
    {
        return $this->rootPath;
    }

    public function getManifestPath(): string
    {
        return $this->manifestPath;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getChecksum(): string
    {
        return $this->checksum;
    }

    public function getDiscoveredAt(): \DateTimeImmutable
    {
        return $this->discoveredAt;
    }
}
