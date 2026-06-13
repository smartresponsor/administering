<?php

declare(strict_types=1);

namespace App\Administering\Entity;

use App\Administering\Repository\AdministrationCredentialDefinitionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Declares a credential required by the host application or one of its components.
 *
 * Credential values are never stored in this entity. Values belong to Symfony Secrets,
 * environment variables, or a future approved vault.
 */
#[ORM\Entity(repositoryClass: AdministrationCredentialDefinitionRepository::class)]
#[ORM\Table(name: 'administration_credential_definition')]
#[ORM\UniqueConstraint(name: 'uniq_administration_credential_definition_key_env', columns: ['credential_key', 'environment_name'])]
class AdministrationCredentialDefinition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'component_name', type: 'string', length: 120)]
    private string $componentName;

    #[ORM\Column(name: 'credential_key', type: 'string', length: 180)]
    private string $credentialKey;

    #[ORM\Column(name: 'environment_name', type: 'string', length: 40)]
    private string $environmentName;

    #[ORM\Column(name: 'source_type', type: 'string', length: 40)]
    private string $sourceType = 'symfony_secret';

    #[ORM\Column(name: 'required', type: 'boolean')]
    private bool $required = true;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    private ?string $description = null;

    public function __construct(string $componentName, string $credentialKey, string $environmentName = 'prod', ?string $description = null, ?int $id = null)
    {
        $this->id = $id;
        $this->componentName = $componentName;
        $this->credentialKey = $credentialKey;
        $this->environmentName = $environmentName;
        $this->description = $description;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function componentName(): string
    {
        return $this->componentName;
    }

    public function credentialKey(): string
    {
        return $this->credentialKey;
    }

    public function environmentName(): string
    {
        return $this->environmentName;
    }

    public function sourceType(): string
    {
        return $this->sourceType;
    }

    public function required(): bool
    {
        return $this->required;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function getId(): ?int
    {
        return $this->id();
    }

    public function getComponentName(): string
    {
        return $this->componentName();
    }

    public function getCredentialKey(): string
    {
        return $this->credentialKey();
    }

    public function getEnvironmentName(): string
    {
        return $this->environmentName();
    }

    public function getSourceType(): string
    {
        return $this->sourceType();
    }

    public function isRequired(): bool
    {
        return $this->required();
    }

    public function getDescription(): ?string
    {
        return $this->description();
    }
}
