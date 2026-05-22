<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\Value\Operation\AdministrationOperationType;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Aggregates the pre-3RC Administering proof commands into one owner/CI gate.
 *
 * This command intentionally delegates to the already explicit proof commands
 * instead of duplicating their lower-level checks. The result is a single
 * machine-readable gate that can be used by owner handoff scripts while still
 * preserving the dedicated commands for manual diagnosis.
 */
#[AsCommand(
    name: 'administering:rc:proof',
    description: 'Runs the Administering readiness, lifecycle, and Messenger-boundary proof gates for RC promotion.',
)]
final class AdministrationRcProofCommand extends Command
{
    /** @var list<string> */
    private const PROOF_COMMANDS = [
        'administering:runtime:readiness',
        'administering:operation:lifecycle-proof',
        'administering:operation:messenger-boundary-proof',
    ];

    protected function configure(): void
    {
        $this
            ->addOption('operation-type', null, InputOption::VALUE_REQUIRED, 'Launchable operation type used for lifecycle and Messenger-boundary proofs.', AdministrationOperationType::CONFIGURATION_SCAN)
            ->addOption('target-prefix', null, InputOption::VALUE_REQUIRED, 'Safe target reference prefix used by proof operation runs.', 'administering:rc-proof')
            ->addOption('output-file', null, InputOption::VALUE_REQUIRED, 'Optional path where the aggregate JSON proof report should be written.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit a machine-readable aggregate RC proof report.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $operationType = trim((string) $input->getOption('operation-type'));
        $targetPrefix = trim((string) $input->getOption('target-prefix'));

        $outputFile = $this->normalizeOutputFile($input->getOption('output-file'));

        $report = [
            'status' => 'not_ready',
            'ready' => false,
            'operation_type' => $operationType,
            'target_prefix' => $targetPrefix,
            'output_file' => $outputFile,
            'commands' => [],
            'errors' => [],
        ];

        foreach (self::PROOF_COMMANDS as $commandName) {
            $result = $this->runProofCommand($commandName, $operationType, $targetPrefix);
            $report['commands'][] = $result;

            if (0 !== $result['exit_code']) {
                $report['errors'][] = sprintf('Proof command failed: %s.', $commandName);
            }

            if (false === $result['json_valid']) {
                $report['errors'][] = sprintf('Proof command did not return valid JSON: %s.', $commandName);
            }
        }

        $ready = [] === $report['errors'];
        $report['ready'] = $ready;
        $report['status'] = $ready ? 'ready' : 'not_ready';

        if (null !== $outputFile) {
            $writeError = $this->writeReport($outputFile, $report);
            if (null !== $writeError) {
                $report['errors'][] = $writeError;
                $report['ready'] = false;
                $report['status'] = 'not_ready';
                $ready = false;
            }
        }

        if ((bool) $input->getOption('json')) {
            $output->writeln((string) json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $ready ? Command::SUCCESS : Command::FAILURE;
        }

        $io->title('Administering RC proof aggregate');
        $definitionList = [
            ['status' => $report['status']],
            ['operation type' => $operationType],
            ['target prefix' => $targetPrefix],
        ];

        if (null !== $outputFile) {
            $definitionList[] = ['output file' => $outputFile];
        }

        $io->definitionList(...$definitionList);

        $rows = [];
        foreach ($report['commands'] as $commandReport) {
            $rows[] = [
                $commandReport['command'],
                0 === $commandReport['exit_code'] ? 'ok' : 'failed',
                $commandReport['json_valid'] ? 'yes' : 'no',
                $commandReport['summary'],
            ];
        }

        $io->table(['Command', 'Exit', 'JSON', 'Summary'], $rows);

        if ([] !== $report['errors']) {
            $io->error($report['errors']);

            return Command::FAILURE;
        }

        $io->success('RC proof aggregate passed.');

        return Command::SUCCESS;
    }

    private function normalizeOutputFile(mixed $outputFile): ?string
    {
        if (!is_string($outputFile)) {
            return null;
        }

        $outputFile = trim($outputFile);

        return '' === $outputFile ? null : $outputFile;
    }

    /**
     * @param array<string, mixed> $report
     */
    private function writeReport(string $outputFile, array $report): ?string
    {
        $directory = dirname($outputFile);

        if ('' !== $directory && '.' !== $directory && !is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            return sprintf('Unable to create RC proof output directory: %s.', $directory);
        }

        $encoded = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (!is_string($encoded)) {
            return 'Unable to encode RC proof report as JSON.';
        }

        if (false === file_put_contents($outputFile, $encoded.PHP_EOL)) {
            return sprintf('Unable to write RC proof output file: %s.', $outputFile);
        }

        return null;
    }

    /**
     * @return array{command: string, exit_code: int, json_valid: bool, summary: string, output: mixed}
     */
    private function runProofCommand(string $commandName, string $operationType, string $targetPrefix): array
    {
        $application = $this->getApplication();

        if (null === $application) {
            return [
                'command' => $commandName,
                'exit_code' => Command::FAILURE,
                'json_valid' => false,
                'summary' => 'console application is not available',
                'output' => null,
            ];
        }

        try {
            $command = $application->find($commandName);
            $arguments = ['command' => $commandName, '--json' => true];

            if ('administering:operation:lifecycle-proof' === $commandName) {
                $arguments['--operation-type'] = $operationType;
                $arguments['--target'] = $targetPrefix.':lifecycle';
            }

            if ('administering:operation:messenger-boundary-proof' === $commandName) {
                $arguments['--operation-type'] = $operationType;
                $arguments['--target'] = $targetPrefix.':messenger-boundary';
            }

            $buffer = new BufferedOutput();
            $exitCode = $command->run(new ArrayInput($arguments), $buffer);
            $rawOutput = trim($buffer->fetch());
            $decoded = json_decode($rawOutput, true);
            $jsonValid = JSON_ERROR_NONE === json_last_error() && is_array($decoded);

            return [
                'command' => $commandName,
                'exit_code' => $exitCode,
                'json_valid' => $jsonValid,
                'summary' => $this->summarize($commandName, $jsonValid ? $decoded : null, $rawOutput),
                'output' => $jsonValid ? $decoded : $this->redactOutput($rawOutput),
            ];
        } catch (\Throwable $throwable) {
            return [
                'command' => $commandName,
                'exit_code' => Command::FAILURE,
                'json_valid' => false,
                'summary' => $this->redactOutput(sprintf('%s: %s', $throwable::class, $throwable->getMessage())),
                'output' => null,
            ];
        }
    }

    /**
     * @param array<string, mixed>|null $decoded
     */
    private function summarize(string $commandName, ?array $decoded, string $rawOutput): string
    {
        if (null === $decoded) {
            return $this->redactOutput($rawOutput);
        }

        if ('administering:runtime:readiness' === $commandName) {
            $status = (string) ($decoded['status'] ?? 'unknown');
            $summary = $decoded['summary'] ?? [];

            if (is_array($summary)) {
                return sprintf(
                    'status=%s, missing_managers=%s, missing_routes=%s, missing_gates=%s, unsupported_operations=%s',
                    $status,
                    (string) ($summary['missing_entity_manager_mappings'] ?? '?'),
                    (string) ($summary['missing_routes'] ?? '?'),
                    (string) ($summary['missing_permission_gates'] ?? '?'),
                    (string) ($summary['unsupported_launchable_operations'] ?? '?'),
                );
            }

            return sprintf('status=%s', $status);
        }

        $status = (string) ($decoded['status'] ?? 'unknown');
        $events = (string) ($decoded['events'] ?? '?');
        $artifacts = (string) ($decoded['artifacts'] ?? '?');
        $successful = true === ($decoded['successful'] ?? false) ? 'yes' : 'no';

        return sprintf('status=%s, successful=%s, events=%s, artifacts=%s', $status, $successful, $events, $artifacts);
    }

    private function redactOutput(string $output): string
    {
        $output = preg_replace('/(secret|token|password|credential|private|authorization|dsn|key)=([^\s]+)/i', '$1=***', $output) ?? $output;

        return mb_substr($output, 0, 800);
    }
}
