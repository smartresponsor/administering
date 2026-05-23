<?php

declare(strict_types=1);

namespace App\Administering\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'administration_environment_runtime_record')]
#[ORM\Index(name: 'idx_administration_environment_category', columns: ['category'])]
#[ORM\Index(name: 'idx_administration_environment_key', columns: ['environment_key'])]
final class AdministrationEnvironmentRuntimeRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 160)]
    private string $environmentKey;

    #[ORM\Column(length: 80)]
    private string $category;

    #[ORM\Column(length: 40)]
    private string $status;

    #[ORM\Column(length: 80)]
    private string $sourceType;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON)]
    private array $safeContext = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $checkedAt;

    /** @param array<string, mixed> $safeContext */
    public function __construct(string $environmentKey, string $category, string $status, string $sourceType, array $safeContext = [])
    {
        $this->environmentKey = $environmentKey;
        $this->category = $category;
        $this->status = $status;
        $this->sourceType = $sourceType;
        $this->safeContext = $safeContext;
        $this->checkedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEnvironmentKey(): string
    {
        return $this->environmentKey;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getSourceType(): string
    {
        return $this->sourceType;
    }

    /** @return array<string, mixed> */
    public function getSafeContext(): array
    {
        return $this->safeContext;
    }

    public function getCheckedAt(): \DateTimeImmutable
    {
        return $this->checkedAt;
    }
}
