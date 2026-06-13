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
 * Validates the terminal Administering 3RC handoff bundle after it has been created.
 *
 * This is intentionally an after-the-fact stale-artifact guard. It proves that the
 * handoff bundle still points to the current terminal status, handoff index,
 * final-seal validation, and manifest files without re-running the whole runtime
 * proof sequence.
 */
#[AsCommand(
    name: 'administering:rc:handoff-bundle:validate',
    description: 'Validates the terminal Administering 3RC handoff bundle against current terminal proof artifacts.',
)]
final class AdministrationRcHandoffBundleValidateCommand extends Command
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
            ->addOption('handoff-bundle-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-handoff-bundle.json.', 'delivery/rc/runtime-proof-results/administering-rc-handoff-bundle.json')
            ->addOption('handoff-bundle-text-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-handoff-bundle.txt.', 'delivery/rc/runtime-proof-results/administering-rc-handoff-bundle.txt')
            ->addOption('output-file', null, InputOption::VALUE_REQUIRED, 'Optional path where the handoff bundle validation JSON should be written.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit the validation report as JSON.');
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
        $handoffBundleFile = $this->pathOption($input->getOption('handoff-bundle-file'));
        $handoffBundleTextFile = $this->pathOption($input->getOption('handoff-bundle-text-file'));
        $outputFile = $this->optionalPathOption($input->getOption('output-file'));

        $checks = [];
        $errors = [];

        $manifest = $this->readYaml($manifestFile, $checks, $errors, 'manifest');
        $terminalStatus = $this->readJson($terminalStatusFile, $checks, $errors, 'terminal_status');
        $terminalStatusValidation = $this->readJson($terminalStatusValidationFile, $checks, $errors, 'terminal_status_validation');
        $handoffIndex = $this->readJson($handoffIndexFile, $checks, $errors, 'handoff_index');
        $handoffIndexValidation = $this->readJson($handoffIndexValidationFile, $checks, $errors, 'handoff_index_validation');
        $finalSealValidation = $this->readJson($finalSealValidationFile, $checks, $errors, 'final_seal_validation');
        $handoffBundle = $this->readJson($handoffBundleFile, $checks, $errors, 'handoff_bundle');
        $handoffBundleText = $this->readText($handoffBundleTextFile, $checks, $errors, 'handoff_bundle_text');
        $this->addCheck($checks, $errors, 'terminal_status_summary_file_exists', is_file($terminalStatusSummaryFile), $terminalStatusSummaryFile);
        $this->addCheck($checks, $errors, 'handoff_index_text_file_exists', is_file($handoffIndexTextFile), $handoffIndexTextFile);

        if (is_array($manifest)) {
            $this->addCheck($checks, $errors, 'manifest_component', 'Administering' === ($manifest['component'] ?? null), 'component=Administering');
            $this->addCheck($checks, $errors, 'manifest_package', 'administering/admin' === ($manifest['package'] ?? null), 'package=administering/admin');
            $this->addCheck($checks, $errors, 'manifest_namespace', 'App\\Administering' === ($manifest['namespace'] ?? null), 'namespace=App\\Administering');
            $this->addCheck($checks, $errors, 'manifest_rc_stage', '3RC-candidate' === ($manifest['rc_stage'] ?? null), 'rc_stage=3RC-candidate');
            $this->addCheck($checks, $errors, 'manifest_handoff_bundle_artifact', isset($manifest['artifacts']['rc_handoff_bundle']), 'artifacts.rc_handoff_bundle exists');
            $this->addCheck($checks, $errors, 'manifest_handoff_bundle_validation_artifact', isset($manifest['artifacts']['rc_handoff_bundle_validation']), 'artifacts.rc_handoff_bundle_validation exists');
        }

        $this->assertStatus($terminalStatus, 'terminal_status', 'sealed_3rc_validated', 'sealed_3rc_validated', $checks, $errors);
        $this->assertStatus($terminalStatusValidation, 'terminal_status_validation', 'terminal_status_valid', 'terminal_status_valid', $checks, $errors);
        $this->assertStatus($handoffIndex, 'handoff_index', '3rc_handoff_index_ready', 'handoff_index_ready', $checks, $errors);
        $this->assertStatus($handoffIndexValidation, 'handoff_index_validation', '3rc_handoff_index_valid', 'handoff_index_valid', $checks, $errors);
        $this->assertStatus($finalSealValidation, 'final_seal_validation', 'final_seal_valid', 'final_seal_valid', $checks, $errors);

        if (is_array($handoffBundle)) {
            $this->addCheck($checks, $errors, 'handoff_bundle_status', '3rc_handoff_bundle_ready' === ($handoffBundle['status'] ?? null), 'status=3rc_handoff_bundle_ready');
            $this->addCheck($checks, $errors, 'handoff_bundle_boolean_true', true === ($handoffBundle['handoff_bundle_ready'] ?? null), 'handoff_bundle_ready=true');
            $this->addCheck($checks, $errors, 'handoff_bundle_errors_empty', [] === ($handoffBundle['errors'] ?? null), 'errors=[]');

            $currentHashes = [
                'manifest' => $this->hashOrNull($manifestFile),
                'terminal_status' => $this->hashOrNull($terminalStatusFile),
                'terminal_status_summary' => $this->hashOrNull($terminalStatusSummaryFile),
                'terminal_status_validation' => $this->hashOrNull($terminalStatusValidationFile),
                'handoff_index' => $this->hashOrNull($handoffIndexFile),
                'handoff_index_text' => $this->hashOrNull($handoffIndexTextFile),
                'handoff_index_validation' => $this->hashOrNull($handoffIndexValidationFile),
                'final_seal_validation' => $this->hashOrNull($finalSealValidationFile),
            ];

            $artifacts = $handoffBundle['artifacts'] ?? null;
            $this->addCheck($checks, $errors, 'handoff_bundle_artifacts_map', is_array($artifacts), 'artifacts map exists');
            if (is_array($artifacts)) {
                foreach ($currentHashes as $nameEntity => $hash) {
                    $artifact = $artifacts[$nameEntity] ?? null;
                    $this->addCheck($checks, $errors, 'handoff_bundle_'.$nameEntity.'_artifact', is_array($artifact), sprintf('artifacts.%s exists', $nameEntity));
                    if (is_array($artifact)) {
                        $this->addCheck($checks, $errors, 'handoff_bundle_'.$nameEntity.'_sha256_current', $hash === ($artifact['sha256'] ?? null), sprintf('%s sha256 matches current file', $nameEntity));
                    }
                }
            }
        }

        if (is_string($handoffBundleText)) {
            $this->addCheck($checks, $errors, 'handoff_bundle_text_status', str_contains($handoffBundleText, 'Status: 3rc_handoff_bundle_ready'), 'handoff bundle text contains ready status');
        }

        $valid = [] === $errors;
        $result = [
            'schema_version' => '1.0',
            'component' => 'Administering',
            'package' => 'administering/admin',
            'namespace' => 'App\\Administering',
            'rc_stage' => '3RC-candidate',
            'status' => $valid ? '3rc_handoff_bundle_valid' : 'blocked',
            'handoff_bundle_valid' => $valid,
            'validated_at_utc' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(\DateTimeInterface::ATOM),
            'artifacts' => [
                'manifest_sha256' => $this->hashOrNull($manifestFile),
                'terminal_status_sha256' => $this->hashOrNull($terminalStatusFile),
                'terminal_status_summary_sha256' => $this->hashOrNull($terminalStatusSummaryFile),
                'terminal_status_validation_sha256' => $this->hashOrNull($terminalStatusValidationFile),
                'handoff_index_sha256' => $this->hashOrNull($handoffIndexFile),
                'handoff_index_text_sha256' => $this->hashOrNull($handoffIndexTextFile),
                'handoff_index_validation_sha256' => $this->hashOrNull($handoffIndexValidationFile),
                'final_seal_validation_sha256' => $this->hashOrNull($finalSealValidationFile),
                'handoff_bundle_sha256' => $this->hashOrNull($handoffBundleFile),
                'handoff_bundle_text_sha256' => $this->hashOrNull($handoffBundleTextFile),
            ],
            'checks' => $checks,
            'errors' => $errors,
        ];

        $encoded = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (!is_string($encoded)) {
            throw new \RuntimeException('Unable to encode handoff bundle validation JSON.');
        }

        if (null !== $outputFile) {
            $this->writeArtifact($outputFile, $encoded);
        }

        if ((bool) $input->getOption('json')) {
            $output->writeln($encoded);
        } elseif ($valid) {
            $io->success('Administering 3RC handoff bundle is valid.');
        } else {
            $io->error($errors);
        }

        return $valid ? Command::SUCCESS : Command::FAILURE;
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

    /** @param list<array{name: string, ok: bool, detail: string}> $checks @param list<string> $errors */
    /**
     * @param list<array{name: string, passed: bool, details: string}> $checks
     * @param list<string>                                             $errors
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

    /** @param list<array{name: string, ok: bool, detail: string}> $checks @param list<string> $errors */
    /**
     * @param list<array{name: string, passed: bool, details: string}> $checks
     * @param list<string>                                             $errors
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
            $this->addCheck($checks, $errors, $label.'_yaml_parseable', $ok, $ok ? 'valid YAML object' : 'YAML root is not a map');

            return $ok ? $decoded : null;
        } catch (\Throwable $exception) {
            $this->addCheck($checks, $errors, $label.'_yaml_parseable', false, $exception->getMessage());

            return null;
        }
    }

    /** @param list<array{name: string, ok: bool, detail: string}> $checks @param list<string> $errors */
    /**
     * @param list<array{name: string, passed: bool, details: string}> $checks
     * @param list<string>                                             $errors
     */
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
    /**
     * @param array<string, mixed>|null                                $payload
     * @param list<array{name: string, passed: bool, details: string}> $checks
     * @param list<string>                                             $errors
     */
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
    /**
     * @param list<array{name: string, passed: bool, details: string}> $checks
     * @param list<string>                                             $errors
     */
    private function addCheck(array &$checks, array &$errors, string $nameEntity, bool $ok, string $detail): void
    {
        $checks[] = ['nameEntity' => $nameEntity, 'passed' => $ok, 'details' => $detail];
        if (!$ok) {
            $errors[] = sprintf('%s: %s', $nameEntity, $detail);
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

    private function writeArtifact(string $file, string $content): void
    {
        $directory = dirname($file);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
        file_put_contents($file, $content."\n");
    }
}
