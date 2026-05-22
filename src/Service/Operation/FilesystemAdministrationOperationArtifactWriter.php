<?php

declare(strict_types=1);

namespace App\Administering\Service\Operation;

use App\Administering\Entity\AdministrationOperationArtifact;
use App\Administering\ServiceInterface\Operation\AdministrationOperationArtifactWriterInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Writes safe JSON operation artifacts under var/administering/operation-artifacts.
 */
final class FilesystemAdministrationOperationArtifactWriter implements AdministrationOperationArtifactWriterInterface
{
    private Filesystem $filesystem;

    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly string $projectDir,
    ) {
        $this->filesystem = new Filesystem();
    }

    /** @param array<string, mixed> $safePayload */
    public function writeJsonArtifact(string $operationKey, string $artifactType, string $safeLabel, array $safePayload): AdministrationOperationArtifact
    {
        $persistedOperationKey = mb_substr($operationKey, 0, 180);
        $operationDirectory = $this->safeSegment($operationKey);
        $artifactType = $this->safeSegment($artifactType);
        $directory = sprintf('%s/var/administering/operation-artifacts/%s', $this->projectDir, $operationDirectory);
        $this->filesystem->mkdir($directory);

        $relativePath = sprintf('var/administering/operation-artifacts/%s/%s.json', $operationDirectory, $artifactType);
        $absolutePath = sprintf('%s/%s', $this->projectDir, $relativePath);
        $encoded = json_encode($this->redactPayload($safePayload), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $this->filesystem->dumpFile($absolutePath, $encoded."\n");

        $artifact = new AdministrationOperationArtifact(
            $persistedOperationKey,
            $artifactType,
            $this->redact($safeLabel),
            $relativePath,
            hash('sha256', $encoded),
            ['format' => 'json', 'path_operation_segment' => $operationDirectory],
        );

        $manager = $this->managerRegistry->getManagerForClass(AdministrationOperationArtifact::class);
        if (null !== $manager) {
            $manager->persist($artifact);
            $manager->flush();
        }

        return $artifact;
    }

    private function safeSegment(string $value): string
    {
        $value = preg_replace('/[^a-zA-Z0-9_.-]+/', '-', $value) ?? $value;
        $value = trim($value, '-.');

        return '' !== $value ? mb_substr($value, 0, 180) : 'operation';
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function redactPayload(array $payload): array
    {
        $redacted = [];
        foreach ($payload as $key => $value) {
            $keyString = (string) $key;
            if ($this->sensitiveKey($keyString)) {
                $redacted[$keyString] = '***';

                continue;
            }

            if (is_array($value)) {
                /** @var array<string, mixed> $nested */
                $nested = $value;
                $redacted[$keyString] = $this->redactPayload($nested);

                continue;
            }

            if (is_string($value)) {
                $redacted[$keyString] = $this->redact($value);

                continue;
            }

            if (is_scalar($value) || null === $value) {
                $redacted[$keyString] = $value;

                continue;
            }

            $redacted[$keyString] = sprintf('[%s]', get_debug_type($value));
        }

        return $redacted;
    }

    private function sensitiveKey(string $key): bool
    {
        return 1 === preg_match('/secret|token|password|credential|private|authorization|dsn|key/i', $key);
    }

    private function redact(string $message): string
    {
        $message = preg_replace('/(secret|token|password|dsn|key)=([^\s]+)/i', '$1=***', $message) ?? $message;

        return mb_substr($message, 0, 180);
    }
}
