<?php

declare(strict_types=1);

namespace App\Administering\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'administration_service_section_record')]
#[ORM\Index(name: 'idx_administration_service_section_key', columns: ['section_key'])]
#[ORM\Index(name: 'idx_administration_service_section_status', columns: ['status'])]
final class AdministrationServiceSectionRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    private string $sectionKey;

    #[ORM\Column(length: 160)]
    private string $label;

    #[ORM\Column(length: 255)]
    private string $serviceDirectory;

    #[ORM\Column(length: 40)]
    private string $status;

    #[ORM\Column]
    private int $toolCount;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON)]
    private array $safeContext = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $synchronizedAt;

    /** @param array<string, mixed> $safeContext */
    public function __construct(string $sectionKey, string $label, string $serviceDirectory, string $status, int $toolCount, array $safeContext = [], ?int $id = null)
    {
        $this->id = $id;
        $this->sectionKey = $sectionKey;
        $this->label = $label;
        $this->serviceDirectory = $serviceDirectory;
        $this->status = $status;
        $this->toolCount = $toolCount;
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

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getServiceDirectory(): string
    {
        return $this->serviceDirectory;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getToolCount(): int
    {
        return $this->toolCount;
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
