<?php

declare(strict_types=1);

namespace App\Administering\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'administration_symfony_route_record')]
#[ORM\Index(name: 'idx_administration_symfony_route_name', columns: ['route_name'])]
#[ORM\Index(name: 'idx_administration_symfony_route_status', columns: ['status_class'])]
final class AdministrationSymfonyRouteRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 190)]
    private string $routeName;

    #[ORM\Column(length: 500)]
    private string $path;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $methods = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $controller;

    #[ORM\Column(nullable: true)]
    private ?int $statusCode;

    #[ORM\Column(length: 40)]
    private string $statusClass;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $checkedAt;

    /** @param list<string> $methods */
    public function __construct(string $routeName, string $path, array $methods = [], ?string $controller = null, ?int $statusCode = null, string $statusClass = 'unchecked', ?int $id = null)
    {
        $this->id = $id;
        $this->routeName = $routeName;
        $this->path = $path;
        $this->methods = $methods;
        $this->controller = $controller;
        $this->statusCode = $statusCode;
        $this->statusClass = $statusClass;
        $this->checkedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRouteName(): string
    {
        return $this->routeName;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /** @return list<string> */
    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getController(): ?string
    {
        return $this->controller;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function getStatusClass(): string
    {
        return $this->statusClass;
    }

    public function getCheckedAt(): \DateTimeImmutable
    {
        return $this->checkedAt;
    }
}
