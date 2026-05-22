<?php

declare(strict_types=1);

namespace App\Administering\Value\Operation;

/**
 * Describes an administrative operation without carrying secret values.
 */
final readonly class AdministrationOperationPlan
{
    private string $operationType;
    private string $targetReference;

    /** @var array<string, mixed> */
    private array $safeContext;

    /** @param array<string, mixed> $safeContext */
    public function __construct(string $operationType, string $targetReference, array $safeContext = [])
    {
        $operationType = trim($operationType);
        if ('' === $operationType || !AdministrationOperationType::isKnown($operationType)) {
            throw new \InvalidArgumentException(sprintf('Unknown Administering operation type "%s".', $operationType));
        }

        $targetReference = trim($targetReference);
        if ('' === $targetReference) {
            throw new \InvalidArgumentException('Administering operation target reference must not be empty.');
        }

        $this->operationType = $operationType;
        $this->targetReference = $targetReference;
        $this->safeContext = $this->redactSensitiveContext($safeContext);
    }

    public function operationType(): string
    {
        return $this->operationType;
    }

    public function targetReference(): string
    {
        return $this->targetReference;
    }

    /** @return array<string, mixed> */
    public function safeContext(): array
    {
        return $this->safeContext;
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    private function redactSensitiveContext(array $context): array
    {
        $redacted = [];

        foreach ($context as $key => $value) {
            $normalizedKey = strtolower((string) $key);
            if (1 === preg_match('/secret|token|password|credential|private|authorization|dsn|key/', $normalizedKey)) {
                $redacted[(string) $key] = '[redacted]';
                continue;
            }

            if (is_array($value)) {
                /** @var array<string, mixed> $nested */
                $nested = $value;
                $redacted[(string) $key] = $this->redactSensitiveContext($nested);
                continue;
            }

            $redacted[(string) $key] = $value;
        }

        return $redacted;
    }
}
