<?php

declare(strict_types=1);

namespace App\Administering\Service\Audit;

use App\Administering\Entity\AdministrationAuditEvent;
use App\Administering\ServiceInterface\Audit\AdministrationAuditRecorderInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Persists safe Administering operator events into the system Entity Manager.
 *
 * This recorder intentionally stores only action metadata and already-sanitized
 * context. User/business data and secrets remain owned by their source
 * components and must not be copied into Administering audit records.
 */
final readonly class DoctrineAdministrationAuditRecorder implements AdministrationAuditRecorderInterface
{
    public function __construct(private ManagerRegistry $managerRegistry)
    {
    }

    /** @param array<string, mixed> $context */
    public function record(string $action, string $subjectIdentifier, array $context = []): void
    {
        $manager = $this->manager();
        $manager->persist(new AdministrationAuditEvent($action, $subjectIdentifier, $this->safeContext($context)));
        $manager->flush();
    }

    private function manager(): \Doctrine\Persistence\ObjectManager
    {
        $manager = $this->managerRegistry->getManagerForClass(AdministrationAuditEvent::class);

        if (null === $manager) {
            throw new \LogicException('No Doctrine manager is configured for Administering audit events. Configure the system SQLite entity manager for App\\Administering entities.');
        }

        return $manager;
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    private function safeContext(array $context): array
    {
        $safe = [];

        foreach ($context as $key => $value) {
            $normalizedKey = (string) $key;

            if (preg_match('/secret|token|password|credential|private|authorization/i', $normalizedKey)) {
                $safe[$normalizedKey] = '[redacted]';
                continue;
            }

            $safe[$normalizedKey] = $this->safeValue($value);
        }

        return $safe;
    }

    private function safeValue(mixed $value): mixed
    {
        if (null === $value || is_scalar($value)) {
            return $value;
        }

        if ($value instanceof \Stringable) {
            return (string) $value;
        }

        if (is_array($value)) {
            $safe = [];

            foreach ($value as $key => $item) {
                $normalizedKey = (string) $key;
                if (preg_match('/secret|token|password|credential|private|authorization/i', $normalizedKey)) {
                    $safe[$normalizedKey] = '[redacted]';
                    continue;
                }

                $safe[$normalizedKey] = $this->safeValue($item);
            }

            return $safe;
        }

        return sprintf('[%s]', get_debug_type($value));
    }
}
