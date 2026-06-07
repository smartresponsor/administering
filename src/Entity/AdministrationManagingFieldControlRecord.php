<?php

declare(strict_types=1);

namespace App\Administering\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'administration_managing_field_control_record')]
#[ORM\Index(name: 'idx_administration_managing_field_resource', columns: ['resource_class'])]
#[ORM\Index(name: 'idx_administration_managing_field_status', columns: ['access_status', 'visibility_status'])]
final class AdministrationManagingFieldControlRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $resourceClass;

    #[ORM\Column(length: 120)]
    private string $fieldName;

    #[ORM\Column(length: 40)]
    private string $pageName;

    #[ORM\Column(length: 120)]
    private string $subjectScope;

    #[ORM\Column(length: 40)]
    private string $accessStatus;

    #[ORM\Column(length: 40)]
    private string $visibilityStatus;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON)]
    private array $safeContext = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $checkedAt;

    /** @param array<string, mixed> $safeContext */
    public function __construct(string $resourceClass, string $fieldName, string $pageName, string $subjectScope, string $accessStatus, string $visibilityStatus, array $safeContext = [], ?int $id = null)
    {
        $this->id = $id;
        $this->resourceClass = $resourceClass;
        $this->fieldName = $fieldName;
        $this->pageName = $pageName;
        $this->subjectScope = $subjectScope;
        $this->accessStatus = $accessStatus;
        $this->visibilityStatus = $visibilityStatus;
        $this->safeContext = $safeContext;
        $this->checkedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getResourceClass(): string
    {
        return $this->resourceClass;
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    public function getPageName(): string
    {
        return $this->pageName;
    }

    public function getSubjectScope(): string
    {
        return $this->subjectScope;
    }

    public function getAccessStatus(): string
    {
        return $this->accessStatus;
    }

    public function getVisibilityStatus(): string
    {
        return $this->visibilityStatus;
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
