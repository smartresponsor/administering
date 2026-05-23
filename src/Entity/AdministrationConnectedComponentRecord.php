<?php

declare(strict_types=1);

namespace App\Administering\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'administration_connected_component_record')]
#[ORM\Index(name: 'idx_administration_connected_component_name', columns: ['component_name'])]
#[ORM\Index(name: 'idx_administration_connected_component_status', columns: ['status'])]
final class AdministrationConnectedComponentRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    private string $componentName;

    #[ORM\Column(length: 40)]
    private string $status;

    #[ORM\Column(length: 40)]
    private string $readinessStatus;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON)]
    private array $safeSummary = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $synchronizedAt;

    /** @param array<string, mixed> $safeSummary */
    public function __construct(string $componentName, string $status, string $readinessStatus, array $safeSummary = [])
    {
        $this->componentName = $componentName;
        $this->status = $status;
        $this->readinessStatus = $readinessStatus;
        $this->safeSummary = $safeSummary;
        $this->synchronizedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getComponentName(): string
    {
        return $this->componentName;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getReadinessStatus(): string
    {
        return $this->readinessStatus;
    }

    /** @return array<string, mixed> */
    public function getSafeSummary(): array
    {
        return $this->safeSummary;
    }

    public function getSynchronizedAt(): \DateTimeImmutable
    {
        return $this->synchronizedAt;
    }
}
