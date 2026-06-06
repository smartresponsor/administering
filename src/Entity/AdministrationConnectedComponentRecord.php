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

    public function getRuntimeScope(): string
    {
        return $this->safeString('runtimeScope');
    }

    public function getComposerPackage(): string
    {
        return $this->safeString('composerPackage');
    }

    public function getBundleToken(): string
    {
        return $this->safeString('bundleToken');
    }

    public function isInstalled(): bool
    {
        return $this->safeBool('present');
    }

    public function isInRuntimeScope(): bool
    {
        return $this->safeBool('allowed');
    }

    public function isEnabledInCurrentScope(): bool
    {
        return $this->safeBool('enabled');
    }

    public function isDisabledByRuntimeLock(): bool
    {
        return $this->safeBool('disabled');
    }

    public function isEnabledForDev(): bool
    {
        return $this->scopeBool('dev', 'enabled');
    }

    public function isEnabledForProd(): bool
    {
        return $this->scopeBool('prod', 'enabled');
    }

    public function getDevDecision(): string
    {
        return $this->scopeString('dev', 'status');
    }

    public function getProdDecision(): string
    {
        return $this->scopeString('prod', 'status');
    }

    private function scopeBool(string $scope, string $key): bool
    {
        $metadata = $this->safeSummary['metadata'] ?? [];
        if (!is_array($metadata)) {
            return false;
        }

        $row = $metadata[$scope] ?? [];
        if (!is_array($row)) {
            return false;
        }

        return true === ($row[$key] ?? false);
    }

    private function scopeString(string $scope, string $key): string
    {
        $metadata = $this->safeSummary['metadata'] ?? [];
        if (!is_array($metadata)) {
            return '';
        }

        $row = $metadata[$scope] ?? [];
        if (!is_array($row)) {
            return '';
        }

        $value = $row[$key] ?? '';

        return is_scalar($value) ? (string) $value : '';
    }

    public function getDecisionReason(): string
    {
        $message = $this->safeSummary['message'] ?? null;

        return is_string($message) ? $message : '';
    }

    private function safeBool(string $key): bool
    {
        $metadata = $this->safeSummary['metadata'] ?? [];
        if (!is_array($metadata)) {
            return false;
        }

        return true === ($metadata[$key] ?? false);
    }

    private function safeString(string $key): string
    {
        $metadata = $this->safeSummary['metadata'] ?? [];
        if (!is_array($metadata)) {
            return '';
        }

        $value = $metadata[$key] ?? '';

        return is_scalar($value) ? (string) $value : '';
    }

    public function getSynchronizedAt(): \DateTimeImmutable
    {
        return $this->synchronizedAt;
    }
}
