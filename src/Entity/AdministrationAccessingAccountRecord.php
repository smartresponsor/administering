<?php

declare(strict_types=1);

namespace App\Administering\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'administration_accessing_account_record')]
#[ORM\Index(name: 'idx_administration_accessing_account_status', columns: ['status'])]
#[ORM\Index(name: 'idx_administration_accessing_account_reference', columns: ['account_reference'])]
final class AdministrationAccessingAccountRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $accountReference;

    #[ORM\Column(length: 190)]
    private string $displayLabel;

    #[ORM\Column(length: 40)]
    private string $status;

    #[ORM\Column(length: 80)]
    private string $provider;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON)]
    private array $safeContext = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $synchronizedAt;

    /** @param array<string, mixed> $safeContext */
    public function __construct(string $accountReference, string $displayLabel, string $status, string $provider, array $safeContext = [], ?int $id = null)
    {
        $this->id = $id;
        $this->accountReference = $accountReference;
        $this->displayLabel = $displayLabel;
        $this->status = $status;
        $this->provider = $provider;
        $this->safeContext = $safeContext;
        $this->synchronizedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAccountReference(): string
    {
        return $this->accountReference;
    }

    public function getDisplayLabel(): string
    {
        return $this->displayLabel;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getProvider(): string
    {
        return $this->provider;
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
