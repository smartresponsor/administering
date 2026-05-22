<?php

declare(strict_types=1);

namespace App\Administering\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Administering-side record of a controlled Accessing account action request.
 *
 * The action is executed by Accessing. Administering stores only request/result
 * metadata for operator traceability and UI visibility.
 */
#[ORM\Entity]
#[ORM\Table(name: 'administration_account_action_request_record')]
#[ORM\Index(name: 'idx_administration_account_action_request_key', columns: ['request_key'])]
#[ORM\Index(name: 'idx_administration_account_action_account', columns: ['account_reference'])]
#[ORM\Index(name: 'idx_administration_account_action_status', columns: ['status'])]
final class AdministrationAccountActionRequestRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'request_key', type: 'string', length: 180)]
    private string $requestKey;

    #[ORM\Column(name: 'action', type: 'string', length: 120)]
    private string $action;

    #[ORM\Column(name: 'account_reference', type: 'string', length: 180)]
    private string $accountReference;

    #[ORM\Column(name: 'requested_by_subject', type: 'string', length: 180)]
    private string $requestedBySubject;

    #[ORM\Column(name: 'status', type: 'string', length: 40)]
    private string $status;

    #[ORM\Column(name: 'safe_reason', type: 'text')]
    private string $safeReason;

    #[ORM\Column(name: 'safe_result_message', type: 'text')]
    private string $safeResultMessage;

    /** @var array<string, mixed> */
    #[ORM\Column(name: 'safe_context', type: Types::JSON)]
    private array $safeContext = [];

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    /** @param array<string, mixed> $safeContext */
    public function __construct(
        string $requestKey,
        string $action,
        string $accountReference,
        string $requestedBySubject,
        string $status,
        string $safeReason,
        string $safeResultMessage,
        array $safeContext = [],
    ) {
        $this->requestKey = $requestKey;
        $this->action = $action;
        $this->accountReference = $accountReference;
        $this->requestedBySubject = $requestedBySubject;
        $this->status = $status;
        $this->safeReason = $safeReason;
        $this->safeResultMessage = $safeResultMessage;
        $this->safeContext = $safeContext;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function requestKey(): string
    {
        return $this->requestKey;
    }

    public function action(): string
    {
        return $this->action;
    }

    public function accountReference(): string
    {
        return $this->accountReference;
    }

    public function requestedBySubject(): string
    {
        return $this->requestedBySubject;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function safeReason(): string
    {
        return $this->safeReason;
    }

    public function safeResultMessage(): string
    {
        return $this->safeResultMessage;
    }

    /** @return array<string, mixed> */
    public function safeContext(): array
    {
        return $this->safeContext;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getId(): ?int
    {
        return $this->id();
    }

    public function getRequestKey(): string
    {
        return $this->requestKey();
    }

    public function getAction(): string
    {
        return $this->action();
    }

    public function getAccountReference(): string
    {
        return $this->accountReference();
    }

    public function getRequestedBySubject(): string
    {
        return $this->requestedBySubject();
    }

    public function getStatus(): string
    {
        return $this->status();
    }

    public function getSafeReason(): string
    {
        return $this->safeReason();
    }

    public function getSafeResultMessage(): string
    {
        return $this->safeResultMessage();
    }

    /** @return array<string, mixed> */
    public function getSafeContext(): array
    {
        return $this->safeContext();
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt();
    }
}
