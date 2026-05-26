<?php

declare(strict_types=1);

namespace App\Administering\Operator\Credential;

use App\Administering\ServiceInterface\Credential\AdministrationCredentialOperatorInterface;
use App\Administering\Value\Credential\AdministrationCredentialOperationResult;
use Symfony\Component\Process\Process;

/**
 * Executes Symfony Secrets operations without persisting secret values.
 */
final class SymfonySecretsAdministrationCredentialOperator implements AdministrationCredentialOperatorInterface
{
    public function __construct(private readonly string $projectDir = '')
    {
    }

    public function list(string $environment): AdministrationCredentialOperationResult
    {
        return $this->run('list', $environment, ['bin/console', 'secrets:list', '--env='.$environment]);
    }

    public function set(string $environment, string $credentialKey, string $plainValue): AdministrationCredentialOperationResult
    {
        $process = new Process(['bin/console', 'secrets:set', $credentialKey, '--env='.$environment], $this->workingDirectory());
        $process->setInput($plainValue.PHP_EOL);
        $process->run();

        return new AdministrationCredentialOperationResult(
            $process->isSuccessful(),
            'set',
            $credentialKey,
            $environment,
            $this->safeMessages($process),
        );
    }

    public function remove(string $environment, string $credentialKey): AdministrationCredentialOperationResult
    {
        return $this->run('remove', $environment, ['bin/console', 'secrets:remove', $credentialKey, '--env='.$environment], $credentialKey);
    }

    /** @param list<string> $command */
    private function run(string $operation, string $environment, array $command, string $credentialKey = '*'): AdministrationCredentialOperationResult
    {
        $process = new Process($command, $this->workingDirectory());
        $process->run();

        return new AdministrationCredentialOperationResult(
            $process->isSuccessful(),
            $operation,
            $credentialKey,
            $environment,
            $this->safeMessages($process),
        );
    }

    /** @return list<string> */
    private function safeMessages(Process $process): array
    {
        $output = trim($process->getOutput());
        $error = trim($process->getErrorOutput());
        $messages = [];
        if ('' !== $output) {
            $messages[] = $this->redact($output);
        }
        if ('' !== $error) {
            $messages[] = $this->redact($error);
        }

        return $messages;
    }

    private function redact(string $message): string
    {
        return (string) preg_replace('/(=|:|\s)([^\s]{12,})/', '$1********', $message);
    }

    private function workingDirectory(): ?string
    {
        return '' === $this->projectDir ? null : $this->projectDir;
    }
}
