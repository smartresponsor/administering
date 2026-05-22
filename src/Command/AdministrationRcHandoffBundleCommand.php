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
 * Builds the terminal Administering 3RC handoff bundle manifest.
 *
 * This command deliberately does not create another proof verdict. It collects
 * the already validated 3RC terminal artifacts into a compact bundle manifest
 * with paths, SHA-256 hashes, statuses, and owner-oriented copy/apply hints so
 * the host application, watchdog, or owner review can consume one stable index.
 */
#[AsCommand(
    name: 'administering:rc:handoff-bundle',
    description: 'Builds the terminal Administering 3RC handoff bundle manifest from validated terminal artifacts.',
)]
final class AdministrationRcHandoffBundleCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('manifest-file', null, InputOption::VALUE_REQUIRED, 'Path to delivery/rc/manifest.yaml.', 'delivery/rc/manifest.yaml')
            ->addOption('terminal-status-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-terminal-status.json.', 'delivery/rc/runtime-proof-results/administering-rc-terminal-status.json')
            ->addOption('terminal-status-summary-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-terminal-status-summary.txt.', 'delivery/rc/runtime-proof-results/administering-rc-terminal-status-summary.txt')
            ->addOption('terminal-status-validation-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-terminal-status-validation.json.', 'delivery/rc/runtime-proof-results/administering-rc-terminal-status-validation.json')
            ->addOption('handoff-index-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-handoff-index.json.', 'delivery/rc/runtime-proof-results/administering-rc-handoff-index.json')
            ->addOption('handoff-index-text-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-handoff-index.txt.', 'delivery/rc/runtime-proof-results/administering-rc-handoff-index.txt')
            ->addOption('handoff-index-validation-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-handoff-index-validation.json.', 'delivery/rc/runtime-proof-results/administering-rc-handoff-index-validation.json')
            ->addOption('final-seal-validation-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-final-seal-validation.json.', 'delivery/rc/runtime-proof-results/administering-rc-final-seal-validation.json')
            ->addOption('output-file', null, InputOption::VALUE_REQUIRED, 'Path where the handoff bundle JSON should be written.', 'delivery/rc/runtime-proof-results/administering-rc-handoff-bundle.json')
            ->addOption('text-file', null, InputOption::VALUE_REQUIRED, 'Path where the handoff bundle text summary should be written.', 'delivery/rc/runtime-proof-results/administering-rc-handoff-bundle.txt')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit the bundle manifest as JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $manifestFile = $this->pathOption($input->getOption('manifest-file'));
        $terminalStatusFile = $this->pathOption($input->getOption('terminal-status-file'));
        $terminalStatusSummaryFile = $this->pathOption($input->getOption('terminal-status-summary-file'));
        $terminalStatusValidationFile = $this->pathOption($input->getOption('terminal-status-validation-file'));
        $handoffIndexFile = $this->pathOption($input->getOption('handoff-index-file'));
        $handoffIndexTextFile = $this->pathOption($input->getOption('handoff-index-text-file'));
        $handoffIndexValidationFile = $this->pathOption($input->getOption('handoff-index-validation-file'));
        $finalSealValidationFile = $this->pathOption($input->getOption('final-seal-validation-file'));
        $outputFile = $this->pathOption($input->getOption('output-file'));
        $textFile = $this->pathOption($input->getOption('text-file'));

        $checks = [];
        $errors = [];

        $manifest = $this->readYaml($manifestFile, $checks, $errors, 'manifest');
        $terminalStatus = $this->readJson($terminalStatusFile, $checks, $errors, 'terminal_status');
        $terminalStatusValidation = $this->readJson($terminalStatusValidationFile, $checks, $errors, 'terminal_status_validation');
        $handoffIndex = $this->readJson($handoffIndexFile, $checks, $errors, 'handoff_index');
        $handoffIndexValidation = $this->readJson($handoffIndexValidationFile, $checks, $errors, 'handoff_index_validation');
        $finalSealValidation = $this->readJson($finalSealValidationFile, $checks, $errors, 'final_seal_validation');
        $terminalStatusSummary = $this->readText($terminalStatusSummaryFile, $checks, $errors, 'terminal_status_summary');
        $handoffIndexText = $this->readText($handoffIndexTextFile, $checks, $errors, 'handoff_index_text');

        if (is_array($manifest)) {
            $this->addCheck($checks, $errors, 'manifest_component', 'Administering' === ($manifest['component'] ?? null), 'component=Administering');
            $this->addCheck($checks, $errors, 'manifest_package', 'administering/admin' === ($manifest['package'] ?? null), 'package=administering/admin');
            $this->addCheck($checks, $errors, 'manifest_namespace', 'App\\Administering' === ($manifest['namespace'] ?? null), 'namespace=App\\Administering');
            $this->addCheck($checks, $errors, 'manifest_rc_stage', '3RC-candidate' === ($manifest['rc_stage'] ?? null), 'rc_stage=3RC-candidate');
        }

        $this->assertStatus($terminalStatus, 'terminal_status', 'sealed_3rc_validated', 'sealed_3rc_validated', $checks, $errors);
        $this->assertStatus($terminalStatusValidation, 'terminal_status_validation', 'terminal_status_valid', 'terminal_status_valid', $checks, $errors);
        $this->assertStatus($handoffIndex, 'handoff_index', '3rc_handoff_index_ready', 'handoff_index_ready', $checks, $errors);
        $this->assertStatus($handoffIndexValidation, 'handoff_index_validation', '3rc_handoff_index_valid', 'handoff_index_valid', $checks, $errors);
        $this->assertStatus($finalSealValidation, 'final_seal_validation', 'final_seal_valid', 'final_seal_valid', $checks, $errors);

        if (is_string($terminalStatusSummary)) {
            $this->addCheck($checks, $errors, 'terminal_summary_contains_status', str_contains($terminalStatusSummary, 'Status: sealed_3rc_validated'), 'terminal summary contains sealed status');
        }
        if (is_string($handoffIndexText)) {
            $this->addCheck($checks, $errors, 'handoff_text_contains_status', str_contains($handoffIndexText, '3rc_handoff_index_ready'), 'handoff index text contains ready status');
        }

        $artifactFiles = [
            'manifest' => $manifestFile,
            'terminal_status' => $terminalStatusFile,
            'terminal_status_summary' => $terminalStatusSummaryFile,
            'terminal_status_validation' => $terminalStatusValidationFile,
            'handoff_index' => $handoffIndexFile,
            'handoff_index_text' => $handoffIndexTextFile,
            'handoff_index_validation' => $handoffIndexValidationFile,
            'final_seal_validation' => $finalSealValidationFile,
        ];

        $artifacts = [];
        foreach ($artifactFiles as $name => $file) {
            $artifacts[$name] = [
                'path' => $file,
                'sha256' => $this->hashOrNull($file),
                'bytes' => is_file($file) ? filesize($file) : null,
            ];
        }

        $ready = [] === $errors;
        $bundle = [
            'schema_version' => '1.0',
            'component' => 'Administering',
            'package' => 'administering/admin',
            'namespace' => 'App\\Administering',
            'rc_stage' => '3RC-candidate',
            'status' => $ready ? '3rc_handoff_bundle_ready' : 'blocked',
            'handoff_bundle_ready' => $ready,
            'generated_at_utc' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(\DateTimeInterface::ATOM),
            'entrypoints' => [
                'composer' => 'composer quality:rc-3rc',
                'windows_helper' => 'tools/runtime/capture-administering-rc-proof.ps1',
                'terminal_status_summary' => $terminalStatusSummaryFile,
                'handoff_index_text' => $handoffIndexTextFile,
            ],
            'terminal_status' => is_array($terminalStatus) ? [
                'status' => $terminalStatus['status'] ?? null,
                'sealed_3rc_validated' => $terminalStatus['sealed_3rc_validated'] ?? null,
                'errors' => $terminalStatus['errors'] ?? null,
            ] : null,
            'terminal_validation' => is_array($terminalStatusValidation) ? [
                'status' => $terminalStatusValidation['status'] ?? null,
                'terminal_status_valid' => $terminalStatusValidation['terminal_status_valid'] ?? null,
                'errors' => $terminalStatusValidation['errors'] ?? null,
            ] : null,
            'handoff_index' => is_array($handoffIndex) ? [
                'status' => $handoffIndex['status'] ?? null,
                'handoff_index_ready' => $handoffIndex['handoff_index_ready'] ?? null,
                'errors' => $handoffIndex['errors'] ?? null,
            ] : null,
            'handoff_validation' => is_array($handoffIndexValidation) ? [
                'status' => $handoffIndexValidation['status'] ?? null,
                'handoff_index_valid' => $handoffIndexValidation['handoff_index_valid'] ?? null,
                'errors' => $handoffIndexValidation['errors'] ?? null,
            ] : null,
            'artifacts' => $artifacts,
            'checks' => $checks,
            'errors' => $errors,
        ];

        $encoded = (string) json_encode($bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $this->writeArtifact($outputFile, $encoded);
        $this->writeArtifact($textFile, $this->buildTextSummary($bundle));

        if ((bool) $input->getOption('json')) {
            $output->writeln($encoded);

            return $ready ? Command::SUCCESS : Command::FAILURE;
        }

        $io->title('Administering 3RC handoff bundle');
        $io->definitionList(
            ['status' => $bundle['status']],
            ['component' => $bundle['component']],
            ['bundle JSON' => $outputFile],
            ['bundle text' => $textFile],
        );

        if ([] !== $errors) {
            $io->error($errors);

            return Command::FAILURE;
        }

        $io->success('Administering 3RC handoff bundle is ready.');

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

    /** @param list<array{name: string, ok: bool, detail: string}> $checks @param list<string> $errors */
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

    /** @param list<array{name: string, ok: bool, detail: string}> $checks @param list<string> $errors */
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
            $this->addCheck($checks, $errors, $label.'_yaml_parseable', $ok, $ok ? 'valid YAML object' : 'YAML root is not a map');

            return $ok ? $decoded : null;
        } catch (\Throwable $exception) {
            $this->addCheck($checks, $errors, $label.'_yaml_parseable', false, $exception->getMessage());

            return null;
        }
    }

    /** @param list<array{name: string, ok: bool, detail: string}> $checks @param list<string> $errors */
    private function readText(string $file, array &$checks, array &$errors, string $label): ?string
    {
        if (!is_file($file)) {
            $this->addCheck($checks, $errors, $label.'_file_exists', false, $file);

            return null;
        }
        $this->addCheck($checks, $errors, $label.'_file_exists', true, $file);

        return (string) file_get_contents($file);
    }

    /** @param list<array{name: string, ok: bool, detail: string}> $checks @param list<string> $errors */
    private function assertStatus(?array $payload, string $label, string $expectedStatus, string $booleanField, array &$checks, array &$errors): void
    {
        if (null === $payload) {
            $this->addCheck($checks, $errors, $label.'_payload_present', false, 'payload missing');

            return;
        }

        $this->addCheck($checks, $errors, $label.'_status', $expectedStatus === ($payload['status'] ?? null), sprintf('status=%s', $expectedStatus));
        $this->addCheck($checks, $errors, $label.'_'.$booleanField, true === ($payload[$booleanField] ?? null), sprintf('%s=true', $booleanField));
        $this->addCheck($checks, $errors, $label.'_errors_empty', [] === ($payload['errors'] ?? null), 'errors=[]');
    }

    /** @param list<array{name: string, ok: bool, detail: string}> $checks @param list<string> $errors */
    private function addCheck(array &$checks, array &$errors, string $name, bool $ok, string $detail): void
    {
        $checks[] = ['name' => $name, 'ok' => $ok, 'detail' => $detail];
        if (!$ok) {
            $errors[] = sprintf('%s: %s', $name, $detail);
        }
    }

    private function hashOrNull(string $file): ?string
    {
        return is_file($file) ? hash_file('sha256', $file) : null;
    }

    private function writeArtifact(string $file, string $content): void
    {
        $directory = dirname($file);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
        file_put_contents($file, $content."\n");
    }

    /** @param array<string, mixed> $bundle */
    private function buildTextSummary(array $bundle): string
    {
        $lines = [
            'Administering 3RC handoff bundle',
            'Status: '.(string) $bundle['status'],
            'Component: Administering',
            'Package: administering/admin',
            'Namespace: App\\Administering',
            'RC stage: 3RC-candidate',
            'Composer: composer quality:rc-3rc',
            '',
            'Artifacts:',
        ];

        $artifacts = is_array($bundle['artifacts'] ?? null) ? $bundle['artifacts'] : [];
        foreach ($artifacts as $name => $artifact) {
            if (!is_array($artifact)) {
                continue;
            }
            $lines[] = sprintf('- %s: %s sha256=%s bytes=%s', $name, (string) ($artifact['path'] ?? ''), (string) ($artifact['sha256'] ?? 'missing'), (string) ($artifact['bytes'] ?? 'missing'));
        }

        $errors = is_array($bundle['errors'] ?? null) ? $bundle['errors'] : [];
        $lines[] = '';
        $lines[] = 'Errors: '.([] === $errors ? 'none' : implode('; ', array_map('strval', $errors)));

        return implode("\n", $lines);
    }
}
