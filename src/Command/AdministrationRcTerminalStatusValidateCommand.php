<?php

declare(strict_types=1);

namespace App\Administering\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

/**
 * Validates the terminal Administering 3RC status artifact after handoff-index validation.
 *
 * This command is the final after-the-fact guard for the owner/watchdog terminal
 * status files. It proves the terminal status JSON and summary still refer to
 * the current handoff index, handoff-index validation, final-status validation,
 * final-seal validation, and manifest artifacts instead of trusting a stale
 * status artifact.
 */
#[AsCommand(
    name: 'administering:rc:terminal-status:validate',
    description: 'Validates the terminal Administering 3RC status artifact against current handoff and seal artifacts.',
)]
final class AdministrationRcTerminalStatusValidateCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('manifest-file', null, InputOption::VALUE_REQUIRED, 'Path to delivery/rc/manifest.yaml.', 'delivery/rc/manifest.yaml')
            ->addOption('terminal-status-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-terminal-status.json.', 'delivery/rc/runtime-proof-results/administering-rc-terminal-status.json')
            ->addOption('terminal-status-summary-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-terminal-status-summary.txt.', 'delivery/rc/runtime-proof-results/administering-rc-terminal-status-summary.txt')
            ->addOption('handoff-index-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-handoff-index.json.', 'delivery/rc/runtime-proof-results/administering-rc-handoff-index.json')
            ->addOption('handoff-index-text-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-handoff-index.txt.', 'delivery/rc/runtime-proof-results/administering-rc-handoff-index.txt')
            ->addOption('handoff-index-validation-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-handoff-index-validation.json.', 'delivery/rc/runtime-proof-results/administering-rc-handoff-index-validation.json')
            ->addOption('final-status-validation-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-final-status-validation.json.', 'delivery/rc/runtime-proof-results/administering-rc-final-status-validation.json')
            ->addOption('final-seal-validation-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-final-seal-validation.json.', 'delivery/rc/runtime-proof-results/administering-rc-final-seal-validation.json')
            ->addOption('output-file', null, InputOption::VALUE_REQUIRED, 'Optional path where the terminal status validation JSON should be written.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit the validation report as JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $manifestFile = $this->pathOption($input->getOption('manifest-file'));
        $terminalStatusFile = $this->pathOption($input->getOption('terminal-status-file'));
        $terminalStatusSummaryFile = $this->pathOption($input->getOption('terminal-status-summary-file'));
        $handoffIndexFile = $this->pathOption($input->getOption('handoff-index-file'));
        $handoffIndexTextFile = $this->pathOption($input->getOption('handoff-index-text-file'));
        $handoffIndexValidationFile = $this->pathOption($input->getOption('handoff-index-validation-file'));
        $finalStatusValidationFile = $this->pathOption($input->getOption('final-status-validation-file'));
        $finalSealValidationFile = $this->pathOption($input->getOption('final-seal-validation-file'));
        $outputFile = $this->optionalPathOption($input->getOption('output-file'));

        $checks = [];
        $errors = [];

        $manifest = $this->readYaml($manifestFile, $checks, $errors, 'manifest');
        $terminalStatus = $this->readJson($terminalStatusFile, $checks, $errors, 'terminal_status');
        $handoffIndex = $this->readJson($handoffIndexFile, $checks, $errors, 'handoff_index');
        $handoffIndexValidation = $this->readJson($handoffIndexValidationFile, $checks, $errors, 'handoff_index_validation');
        $finalStatusValidation = $this->readJson($finalStatusValidationFile, $checks, $errors, 'final_status_validation');
        $finalSealValidation = $this->readJson($finalSealValidationFile, $checks, $errors, 'final_seal_validation');
        $terminalStatusSummary = $this->readText($terminalStatusSummaryFile, $checks, $errors, 'terminal_status_summary');
        $this->addCheck($checks, $errors, 'handoff_index_text_file_exists', is_file($handoffIndexTextFile), $handoffIndexTextFile);

        if (is_array($manifest)) {
            $this->addCheck($checks, $errors, 'manifest_component', 'Administering' === ($manifest['component'] ?? null), 'component=Administering');
            $this->addCheck($checks, $errors, 'manifest_package', 'administering/admin' === ($manifest['package'] ?? null), 'package=administering/admin');
            $this->addCheck($checks, $errors, 'manifest_namespace', 'App\\Administering' === ($manifest['namespace'] ?? null), 'namespace=App\\Administering');
            $this->addCheck($checks, $errors, 'manifest_rc_stage', '3RC-candidate' === ($manifest['rc_stage'] ?? null), 'rc_stage=3RC-candidate');
            $this->addCheck($checks, $errors, 'manifest_terminal_status_artifact', isset($manifest['artifacts']['rc_terminal_status']), 'artifacts.rc_terminal_status exists');
            $this->addCheck($checks, $errors, 'manifest_terminal_status_summary_artifact', isset($manifest['artifacts']['rc_terminal_status_summary']), 'artifacts.rc_terminal_status_summary exists');
            $this->addCheck($checks, $errors, 'manifest_terminal_status_validation_artifact', isset($manifest['artifacts']['rc_terminal_status_validation']), 'artifacts.rc_terminal_status_validation exists');
        }

        $this->assertStatus($handoffIndex, 'handoff_index', '3rc_handoff_index_ready', 'handoff_index_ready', $checks, $errors);
        $this->assertStatus($handoffIndexValidation, 'handoff_index_validation', '3rc_handoff_index_valid', 'handoff_index_valid', $checks, $errors);
        $this->assertStatus($finalStatusValidation, 'final_status_validation', 'final_status_valid', 'final_status_valid', $checks, $errors);
        $this->assertStatus($finalSealValidation, 'final_seal_validation', 'final_seal_valid', 'final_seal_valid', $checks, $errors);

        $currentHashes = [
            'manifest_sha256' => $this->hashOrNull($manifestFile),
            'terminal_status_sha256' => $this->hashOrNull($terminalStatusFile),
            'terminal_status_summary_sha256' => $this->hashOrNull($terminalStatusSummaryFile),
            'handoff_index_sha256' => $this->hashOrNull($handoffIndexFile),
            'handoff_index_text_sha256' => $this->hashOrNull($handoffIndexTextFile),
            'handoff_index_validation_sha256' => $this->hashOrNull($handoffIndexValidationFile),
            'final_status_validation_sha256' => $this->hashOrNull($finalStatusValidationFile),
            'final_seal_validation_sha256' => $this->hashOrNull($finalSealValidationFile),
        ];

        if (is_array($terminalStatus)) {
            $this->addCheck($checks, $errors, 'terminal_status_status', 'sealed_3rc_validated' === ($terminalStatus['status'] ?? null), 'status=sealed_3rc_validated');
            $this->addCheck($checks, $errors, 'terminal_status_boolean_true', true === ($terminalStatus['sealed_3rc_validated'] ?? null), 'sealed_3rc_validated=true');
            $this->addCheck($checks, $errors, 'terminal_status_include_receipt_artifacts', true === ($terminalStatus['include_receipt_artifacts'] ?? null), 'include_receipt_artifacts=true');
            $this->addCheck($checks, $errors, 'terminal_status_include_handoff_artifacts', true === ($terminalStatus['include_handoff_artifacts'] ?? null), 'include_handoff_artifacts=true');
            $this->addCheck($checks, $errors, 'terminal_status_errors_empty', [] === ($terminalStatus['errors'] ?? null), 'errors=[]');

            $artifactStatus = $terminalStatus['artifact_status'] ?? null;
            $this->addCheck($checks, $errors, 'terminal_status_artifact_status_map', is_array($artifactStatus), 'artifact_status map exists');
            if (is_array($artifactStatus)) {
                $this->addCheck($checks, $errors, 'terminal_status_handoff_index_status', '3rc_handoff_index_ready' === ($artifactStatus['handoff_index'] ?? null), 'artifact_status.handoff_index=3rc_handoff_index_ready');
                $this->addCheck($checks, $errors, 'terminal_status_handoff_index_validation_status', '3rc_handoff_index_valid' === ($artifactStatus['handoff_index_validation'] ?? null), 'artifact_status.handoff_index_validation=3rc_handoff_index_valid');
                $this->addCheck($checks, $errors, 'terminal_status_final_seal_validation_status', 'final_seal_valid' === ($artifactStatus['final_seal_validation'] ?? null), 'artifact_status.final_seal_validation=final_seal_valid');
            }

            $artifacts = $terminalStatus['artifacts'] ?? null;
            $this->addCheck($checks, $errors, 'terminal_status_artifacts_map', is_array($artifacts), 'artifacts map exists');
            if (is_array($artifacts)) {
                foreach ($currentHashes as $name => $currentHash) {
                    if ('terminal_status_sha256' === $name || 'terminal_status_summary_sha256' === $name) {
                        continue;
                    }

                    $this->addCheck(
                        $checks,
                        $errors,
                        sprintf('terminal_status_%s_current', $name),
                        is_string($currentHash) && $currentHash === ($artifacts[$name] ?? null),
                        sprintf('%s matches current file', $name),
                    );
                }
            }
        }

        if (is_string($terminalStatusSummary)) {
            $this->addCheck($checks, $errors, 'terminal_status_summary_contains_status', str_contains($terminalStatusSummary, 'Status: sealed_3rc_validated'), 'summary contains sealed status');
            $handoffValidationHash = $currentHashes['handoff_index_validation_sha256'];
            $this->addCheck($checks, $errors, 'terminal_status_summary_contains_handoff_validation_hash', null !== $handoffValidationHash && str_contains($terminalStatusSummary, $handoffValidationHash), 'summary contains handoff-index-validation SHA-256');
        }

        $valid = [] === $errors;
        $report = [
            'schema_version' => '1.0',
            'component' => 'Administering',
            'package' => 'administering/admin',
            'namespace' => 'App\\Administering',
            'rc_stage' => '3RC-candidate',
            'status' => $valid ? 'terminal_status_valid' : 'blocked',
            'terminal_status_valid' => $valid,
            'validated_at_utc' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(\DateTimeInterface::ATOM),
            'artifacts' => [
                'manifest_file' => $manifestFile,
                'terminal_status_file' => $terminalStatusFile,
                'terminal_status_summary_file' => $terminalStatusSummaryFile,
                'handoff_index_file' => $handoffIndexFile,
                'handoff_index_text_file' => $handoffIndexTextFile,
                'handoff_index_validation_file' => $handoffIndexValidationFile,
                'final_status_validation_file' => $finalStatusValidationFile,
                'final_seal_validation_file' => $finalSealValidationFile,
                'manifest_sha256' => $currentHashes['manifest_sha256'],
                'terminal_status_sha256' => $currentHashes['terminal_status_sha256'],
                'terminal_status_summary_sha256' => $currentHashes['terminal_status_summary_sha256'],
                'handoff_index_sha256' => $currentHashes['handoff_index_sha256'],
                'handoff_index_text_sha256' => $currentHashes['handoff_index_text_sha256'],
                'handoff_index_validation_sha256' => $currentHashes['handoff_index_validation_sha256'],
                'final_status_validation_sha256' => $currentHashes['final_status_validation_sha256'],
                'final_seal_validation_sha256' => $currentHashes['final_seal_validation_sha256'],
            ],
            'checks' => $checks,
            'errors' => $errors,
        ];

        $encodedReport = (string) json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (null !== $outputFile) {
            $this->writeJsonArtifact($outputFile, $encodedReport);
        }

        if ((bool) $input->getOption('json')) {
            $output->writeln($encodedReport);

            return $valid ? Command::SUCCESS : Command::FAILURE;
        }

        $io->title('Administering 3RC terminal status validation');
        $io->definitionList(
            ['status' => $report['status']],
            ['component' => $report['component']],
            ['terminal status' => $terminalStatusFile],
            ['output file' => $outputFile ?? '(not written)'],
        );

        $io->table(['Check', 'Result', 'Detail'], array_map(
            static fn (array $check): array => [$check['name'], $check['ok'] ? 'ok' : 'failed', $check['detail']],
            $checks,
        ));

        if ([] !== $errors) {
            $io->error($errors);

            return Command::FAILURE;
        }

        $io->success('Administering terminal 3RC status is valid and current.');

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

    private function optionalPathOption(mixed $value): ?string
    {
        $path = is_string($value) ? trim($value) : '';

        return '' === $path ? null : $path;
    }

    /**
     * @param list<array{name: string, ok: bool, detail: string}> $checks
     * @param list<string>                                        $errors
     *
     * @return array<string, mixed>|null
     */
    private function readJson(string $file, array &$checks, array &$errors, string $label): ?array
    {
        if (!is_file($file)) {
            $this->addCheck($checks, $errors, $label.'_file_exists', false, $file);

            return null;
        }

        $this->addCheck($checks, $errors, $label.'_file_exists', true, $file);
        $decoded = json_decode((string) file_get_contents($file), true);
        $ok = JSON_ERROR_NONE === json_last_error() && is_array($decoded);
        $this->addCheck($checks, $errors, $label.'_json_parseable', $ok, $ok ? 'valid JSON object' : json_last_error_msg());

        return $ok ? $decoded : null;
    }

    /**
     * @param list<array{name: string, ok: bool, detail: string}> $checks
     * @param list<string>                                        $errors
     *
     * @return array<string, mixed>|null
     */
    private function readYaml(string $file, array &$checks, array &$errors, string $label): ?array
    {
        if (!is_file($file)) {
            $this->addCheck($checks, $errors, $label.'_file_exists', false, $file);

            return null;
        }

        $this->addCheck($checks, $errors, $label.'_file_exists', true, $file);

        try {
            $decoded = Yaml::parseFile($file);
            $ok = is_array($decoded);
            $this->addCheck($checks, $errors, $label.'_yaml_parseable', $ok, $ok ? 'valid YAML map' : 'YAML root is not a map');

            return $ok ? $decoded : null;
        } catch (\Throwable $throwable) {
            $this->addCheck($checks, $errors, $label.'_yaml_parseable', false, $throwable->getMessage());

            return null;
        }
    }

    /**
     * @param list<array{name: string, ok: bool, detail: string}> $checks
     * @param list<string>                                        $errors
     */
    private function readText(string $file, array &$checks, array &$errors, string $label): ?string
    {
        if (!is_file($file)) {
            $this->addCheck($checks, $errors, $label.'_file_exists', false, $file);

            return null;
        }

        $this->addCheck($checks, $errors, $label.'_file_exists', true, $file);
        $contents = file_get_contents($file);
        $ok = is_string($contents);
        $this->addCheck($checks, $errors, $label.'_readable', $ok, $ok ? 'readable text file' : 'file_get_contents failed');

        return $ok ? $contents : null;
    }

    /**
     * @param array<string, mixed>|null                           $decoded
     * @param list<array{name: string, ok: bool, detail: string}> $checks
     * @param list<string>                                        $errors
     */
    private function assertStatus(?array $decoded, string $label, string $expectedStatus, string $boolKey, array &$checks, array &$errors): void
    {
        if (!is_array($decoded)) {
            $this->addCheck($checks, $errors, $label.'_available_for_status_check', false, 'artifact missing or not parseable');

            return;
        }

        $this->addCheck($checks, $errors, $label.'_status', $expectedStatus === ($decoded['status'] ?? null), sprintf('status=%s', $expectedStatus));
        $this->addCheck($checks, $errors, $label.'_'.$boolKey.'_true', true === ($decoded[$boolKey] ?? null), sprintf('%s=true', $boolKey));
        if (array_key_exists('errors', $decoded)) {
            $this->addCheck($checks, $errors, $label.'_errors_empty', [] === ($decoded['errors'] ?? null), 'errors=[]');
        }
    }

    private function hashOrNull(string $file): ?string
    {
        if (!is_file($file)) {
            return null;
        }

        $hash = hash_file('sha256', $file);

        return false === $hash ? null : $hash;
    }

    /**
     * @param list<array{name: string, ok: bool, detail: string}> $checks
     * @param list<string>                                        $errors
     */
    private function addCheck(array &$checks, array &$errors, string $name, bool $ok, string $detail = ''): void
    {
        $checks[] = [
            'name' => $name,
            'ok' => $ok,
            'detail' => $detail,
        ];

        if (!$ok) {
            $errors[] = sprintf('%s failed: %s', $name, $detail);
        }
    }

    private function writeJsonArtifact(string $file, string $contents): void
    {
        $directory = dirname($file);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        file_put_contents($file, $contents);
    }
}
