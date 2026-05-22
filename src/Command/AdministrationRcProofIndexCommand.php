<?php

declare(strict_types=1);

namespace App\Administering\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Writes the small proof-index artifact used by the 3RC handoff contract.
 *
 * The Windows helper has historically built this index directly in PowerShell.
 * This command provides the same behavior through Symfony Console so owner and
 * watchdog flows can run the complete 3RC sequence with composer scripts on any
 * environment that can execute bin/console.
 */
#[AsCommand(
    name: 'administering:rc:proof-index',
    description: 'Builds the Administering 3RC proof-index artifact from proof and manifest files.',
)]
final class AdministrationRcProofIndexCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('proof-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-proof.json.', 'delivery/rc/runtime-proof-results/administering-rc-proof.json')
            ->addOption('manifest-file', null, InputOption::VALUE_REQUIRED, 'Path to delivery/rc/manifest.yaml.', 'delivery/rc/manifest.yaml')
            ->addOption('output-file', null, InputOption::VALUE_REQUIRED, 'Path where administering-rc-proof-index.json should be written.', 'delivery/rc/runtime-proof-results/administering-rc-proof-index.json')
            ->addOption('operation-type', null, InputOption::VALUE_REQUIRED, 'Operation type used by the aggregate proof.', 'administration.configuration.scan')
            ->addOption('target-prefix', null, InputOption::VALUE_REQUIRED, 'Target prefix used by the aggregate proof.', 'administering:rc-proof')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit the generated index JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $proofFile = $this->pathOption($input->getOption('proof-file'));
        $manifestFile = $this->pathOption($input->getOption('manifest-file'));
        $outputFile = $this->pathOption($input->getOption('output-file'));
        $operationType = $this->stringOption($input->getOption('operation-type'), 'operation-type');
        $targetPrefix = $this->stringOption($input->getOption('target-prefix'), 'target-prefix');
        $errors = [];

        if (!is_file($proofFile)) {
            $errors[] = sprintf('Proof file not found: %s', $proofFile);
        }

        if (!is_file($manifestFile)) {
            $errors[] = sprintf('RC manifest not found: %s', $manifestFile);
        }

        $proof = [];
        if (is_file($proofFile)) {
            $decoded = json_decode((string) file_get_contents($proofFile), true);
            if (!is_array($decoded)) {
                $errors[] = sprintf('Proof file is not a valid JSON object: %s', json_last_error_msg());
            } else {
                $proof = $decoded;
            }
        }

        if ([] !== $proof) {
            $proofOperationType = (string) ($proof['operation_type'] ?? '');
            $proofTargetPrefix = (string) ($proof['target_prefix'] ?? '');

            if ('' !== $proofOperationType && $proofOperationType !== $operationType) {
                $errors[] = sprintf('Operation type mismatch: option=%s proof=%s', $operationType, $proofOperationType);
            }

            if ('' !== $proofTargetPrefix && $proofTargetPrefix !== $targetPrefix) {
                $errors[] = sprintf('Target prefix mismatch: option=%s proof=%s', $targetPrefix, $proofTargetPrefix);
            }
        }

        $index = [
            'schema_version' => '1.0',
            'component' => 'Administering',
            'rc_stage' => '3RC-candidate',
            'status' => [] === $errors ? 'captured' : 'blocked',
            'operation_type' => $operationType,
            'target_prefix' => $targetPrefix,
            'manifest_file' => $manifestFile,
            'manifest_sha256' => is_file($manifestFile) ? hash_file('sha256', $manifestFile) : null,
            'proof_file' => $proofFile,
            'proof_sha256' => is_file($proofFile) ? hash_file('sha256', $proofFile) : null,
            'captured_at_utc' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(\DateTimeInterface::ATOM),
            'project_root' => getcwd() ?: '',
            'errors' => $errors,
        ];

        $directory = dirname($outputFile);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        file_put_contents($outputFile, (string) json_encode($index, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        if ((bool) $input->getOption('json')) {
            $output->writeln((string) json_encode($index, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return [] === $errors ? Command::SUCCESS : Command::FAILURE;
        }

        $io->title('Administering 3RC proof index');
        $io->definitionList(
            ['status' => $index['status']],
            ['operation type' => $operationType],
            ['target prefix' => $targetPrefix],
            ['output file' => $outputFile],
        );

        if ([] !== $errors) {
            $io->error($errors);

            return Command::FAILURE;
        }

        $io->success('Proof index captured.');

        return Command::SUCCESS;
    }

    private function pathOption(mixed $value): string
    {
        $path = is_string($value) ? trim($value) : '';

        if ('' === $path) {
            throw new \InvalidArgumentException('Expected a non-empty path option.');
        }

        return $path;
    }

    private function stringOption(mixed $value, string $name): string
    {
        $string = is_string($value) ? trim($value) : '';

        if ('' === $string) {
            throw new \InvalidArgumentException(sprintf('Expected a non-empty %s option.', $name));
        }

        return $string;
    }
}
