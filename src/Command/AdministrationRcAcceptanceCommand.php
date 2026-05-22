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
 * Creates the final owner/watchdog acceptance marker for the Administering 3RC handoff.
 *
 * This command is intentionally terminal and compact: it does not create another
 * proof layer. It verifies the already validated bundle-aware status and handoff
 * bundle validation, then emits one explicit acceptance artifact for owner handoff.
 */
#[AsCommand(
    name: 'administering:rc:acceptance',
    description: 'Creates the terminal Administering 3RC acceptance marker from the validated handoff bundle.',
)]
final class AdministrationRcAcceptanceCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('manifest-file', null, InputOption::VALUE_REQUIRED, 'Path to delivery/rc/manifest.yaml.', 'delivery/rc/manifest.yaml')
            ->addOption('bundle-status-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-bundle-status.json.', 'delivery/rc/runtime-proof-results/administering-rc-bundle-status.json')
            ->addOption('bundle-status-summary-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-bundle-status-summary.txt.', 'delivery/rc/runtime-proof-results/administering-rc-bundle-status-summary.txt')
            ->addOption('handoff-bundle-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-handoff-bundle.json.', 'delivery/rc/runtime-proof-results/administering-rc-handoff-bundle.json')
            ->addOption('handoff-bundle-text-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-handoff-bundle.txt.', 'delivery/rc/runtime-proof-results/administering-rc-handoff-bundle.txt')
            ->addOption('handoff-bundle-validation-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-handoff-bundle-validation.json.', 'delivery/rc/runtime-proof-results/administering-rc-handoff-bundle-validation.json')
            ->addOption('terminal-status-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-terminal-status.json.', 'delivery/rc/runtime-proof-results/administering-rc-terminal-status.json')
            ->addOption('terminal-status-summary-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-terminal-status-summary.txt.', 'delivery/rc/runtime-proof-results/administering-rc-terminal-status-summary.txt')
            ->addOption('terminal-status-validation-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-terminal-status-validation.json.', 'delivery/rc/runtime-proof-results/administering-rc-terminal-status-validation.json')
            ->addOption('handoff-index-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-handoff-index.json.', 'delivery/rc/runtime-proof-results/administering-rc-handoff-index.json')
            ->addOption('handoff-index-text-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-handoff-index.txt.', 'delivery/rc/runtime-proof-results/administering-rc-handoff-index.txt')
            ->addOption('handoff-index-validation-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-handoff-index-validation.json.', 'delivery/rc/runtime-proof-results/administering-rc-handoff-index-validation.json')
            ->addOption('final-seal-validation-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-final-seal-validation.json.', 'delivery/rc/runtime-proof-results/administering-rc-final-seal-validation.json')
            ->addOption('output-file', null, InputOption::VALUE_REQUIRED, 'Optional path where the acceptance JSON should be written.', 'delivery/rc/runtime-proof-results/administering-rc-acceptance.json')
            ->addOption('text-file', null, InputOption::VALUE_REQUIRED, 'Optional path where the owner-facing acceptance text should be written.', 'delivery/rc/runtime-proof-results/administering-rc-acceptance.txt')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit the acceptance report as JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $manifestFile = $this->pathOption($input->getOption('manifest-file'));
        $bundleStatusFile = $this->pathOption($input->getOption('bundle-status-file'));
        $bundleStatusSummaryFile = $this->pathOption($input->getOption('bundle-status-summary-file'));
        $handoffBundleFile = $this->pathOption($input->getOption('handoff-bundle-file'));
        $handoffBundleTextFile = $this->pathOption($input->getOption('handoff-bundle-text-file'));
        $handoffBundleValidationFile = $this->pathOption($input->getOption('handoff-bundle-validation-file'));
        $terminalStatusFile = $this->pathOption($input->getOption('terminal-status-file'));
        $terminalStatusSummaryFile = $this->pathOption($input->getOption('terminal-status-summary-file'));
        $terminalStatusValidationFile = $this->pathOption($input->getOption('terminal-status-validation-file'));
        $handoffIndexFile = $this->pathOption($input->getOption('handoff-index-file'));
        $handoffIndexTextFile = $this->pathOption($input->getOption('handoff-index-text-file'));
        $handoffIndexValidationFile = $this->pathOption($input->getOption('handoff-index-validation-file'));
        $finalSealValidationFile = $this->pathOption($input->getOption('final-seal-validation-file'));
        $outputFile = $this->optionalPathOption($input->getOption('output-file'));
        $textFile = $this->optionalPathOption($input->getOption('text-file'));

        $checks = [];
        $errors = [];

        $manifest = $this->readYaml($manifestFile, $checks, $errors, 'manifest');
        $bundleStatus = $this->readJson($bundleStatusFile, $checks, $errors, 'bundle_status');
        $handoffBundle = $this->readJson($handoffBundleFile, $checks, $errors, 'handoff_bundle');
        $handoffBundleValidation = $this->readJson($handoffBundleValidationFile, $checks, $errors, 'handoff_bundle_validation');
        $bundleStatusSummary = $this->readText($bundleStatusSummaryFile, $checks, $errors, 'bundle_status_summary');
        $handoffBundleText = $this->readText($handoffBundleTextFile, $checks, $errors, 'handoff_bundle_text');

        if (is_array($manifest)) {
            $this->addCheck($checks, $errors, 'manifest_component', 'Administering' === ($manifest['component'] ?? null), 'component=Administering');
            $this->addCheck($checks, $errors, 'manifest_package', 'administering/admin' === ($manifest['package'] ?? null), 'package=administering/admin');
            $this->addCheck($checks, $errors, 'manifest_namespace', 'App\\Administering' === ($manifest['namespace'] ?? null), 'namespace=App\\Administering');
            $this->addCheck($checks, $errors, 'manifest_rc_stage', '3RC-candidate' === ($manifest['rc_stage'] ?? null), 'rc_stage=3RC-candidate');
            $this->addCheck($checks, $errors, 'manifest_acceptance_artifact', isset($manifest['artifacts']['rc_acceptance']), 'artifacts.rc_acceptance exists');
            $this->addCheck($checks, $errors, 'manifest_acceptance_text_artifact', isset($manifest['artifacts']['rc_acceptance_text']), 'artifacts.rc_acceptance_text exists');
        }

        if (is_array($bundleStatus)) {
            $this->addCheck($checks, $errors, 'bundle_status_status', 'sealed_3rc_validated' === ($bundleStatus['status'] ?? null), 'status=sealed_3rc_validated');
            $this->addCheck($checks, $errors, 'bundle_status_boolean_true', true === ($bundleStatus['sealed_3rc_validated'] ?? null), 'sealed_3rc_validated=true');
            $this->addCheck($checks, $errors, 'bundle_status_errors_empty', [] === ($bundleStatus['errors'] ?? null), 'errors=[]');

            $bundleStatusArtifacts = $bundleStatus['artifacts'] ?? null;
            $this->addCheck($checks, $errors, 'bundle_status_artifacts_map', is_array($bundleStatusArtifacts), 'bundle status artifacts map exists');
            if (is_array($bundleStatusArtifacts)) {
                $this->addCheck($checks, $errors, 'bundle_status_manifest_hash_current', $this->hashMatches($manifestFile, $bundleStatusArtifacts['manifest_sha256'] ?? null), 'manifest hash matches current file');
                $this->addCheck($checks, $errors, 'bundle_status_handoff_bundle_hash_current', $this->hashMatches($handoffBundleFile, $bundleStatusArtifacts['handoff_bundle_sha256'] ?? null), 'handoff bundle hash matches current file');
                $this->addCheck($checks, $errors, 'bundle_status_handoff_bundle_text_hash_current', $this->hashMatches($handoffBundleTextFile, $bundleStatusArtifacts['handoff_bundle_text_sha256'] ?? null), 'handoff bundle text hash matches current file');
                $this->addCheck($checks, $errors, 'bundle_status_handoff_bundle_validation_hash_current', $this->hashMatches($handoffBundleValidationFile, $bundleStatusArtifacts['handoff_bundle_validation_sha256'] ?? null), 'handoff bundle validation hash matches current file');
            }
        }

        if (is_array($handoffBundle)) {
            $this->addCheck($checks, $errors, 'handoff_bundle_status', '3rc_handoff_bundle_ready' === ($handoffBundle['status'] ?? null), 'status=3rc_handoff_bundle_ready');
            $this->addCheck($checks, $errors, 'handoff_bundle_ready', true === ($handoffBundle['handoff_bundle_ready'] ?? null), 'handoff_bundle_ready=true');
            $this->addCheck($checks, $errors, 'handoff_bundle_errors_empty', [] === ($handoffBundle['errors'] ?? null), 'errors=[]');
        }

        if (is_array($handoffBundleValidation)) {
            $this->addCheck($checks, $errors, 'handoff_bundle_validation_status', '3rc_handoff_bundle_valid' === ($handoffBundleValidation['status'] ?? null), 'status=3rc_handoff_bundle_valid');
            $this->addCheck($checks, $errors, 'handoff_bundle_validation_boolean_true', true === ($handoffBundleValidation['handoff_bundle_valid'] ?? null), 'handoff_bundle_valid=true');
            $this->addCheck($checks, $errors, 'handoff_bundle_validation_errors_empty', [] === ($handoffBundleValidation['errors'] ?? null), 'errors=[]');

            $handoffBundleValidationArtifacts = $handoffBundleValidation['artifacts'] ?? null;
            $this->addCheck($checks, $errors, 'handoff_bundle_validation_artifacts_map', is_array($handoffBundleValidationArtifacts), 'handoff bundle validation artifacts map exists');
            if (is_array($handoffBundleValidationArtifacts)) {
                $this->addCheck($checks, $errors, 'handoff_bundle_validation_manifest_hash_current', $this->hashMatches($manifestFile, $handoffBundleValidationArtifacts['manifest_sha256'] ?? null), 'manifest hash matches current file');
                $this->addCheck($checks, $errors, 'handoff_bundle_validation_terminal_status_hash_current', $this->hashMatches($terminalStatusFile, $handoffBundleValidationArtifacts['terminal_status_sha256'] ?? null), 'terminal-status hash matches current file');
                $this->addCheck($checks, $errors, 'handoff_bundle_validation_terminal_status_summary_hash_current', $this->hashMatches($terminalStatusSummaryFile, $handoffBundleValidationArtifacts['terminal_status_summary_sha256'] ?? null), 'terminal-status summary hash matches current file');
                $this->addCheck($checks, $errors, 'handoff_bundle_validation_terminal_status_validation_hash_current', $this->hashMatches($terminalStatusValidationFile, $handoffBundleValidationArtifacts['terminal_status_validation_sha256'] ?? null), 'terminal-status validation hash matches current file');
                $this->addCheck($checks, $errors, 'handoff_bundle_validation_handoff_index_hash_current', $this->hashMatches($handoffIndexFile, $handoffBundleValidationArtifacts['handoff_index_sha256'] ?? null), 'handoff-index hash matches current file');
                $this->addCheck($checks, $errors, 'handoff_bundle_validation_handoff_index_text_hash_current', $this->hashMatches($handoffIndexTextFile, $handoffBundleValidationArtifacts['handoff_index_text_sha256'] ?? null), 'handoff-index text hash matches current file');
                $this->addCheck($checks, $errors, 'handoff_bundle_validation_handoff_index_validation_hash_current', $this->hashMatches($handoffIndexValidationFile, $handoffBundleValidationArtifacts['handoff_index_validation_sha256'] ?? null), 'handoff-index validation hash matches current file');
                $this->addCheck($checks, $errors, 'handoff_bundle_validation_final_seal_validation_hash_current', $this->hashMatches($finalSealValidationFile, $handoffBundleValidationArtifacts['final_seal_validation_sha256'] ?? null), 'final-seal validation hash matches current file');
                $this->addCheck($checks, $errors, 'handoff_bundle_validation_handoff_bundle_hash_current', $this->hashMatches($handoffBundleFile, $handoffBundleValidationArtifacts['handoff_bundle_sha256'] ?? null), 'handoff bundle hash matches current file');
                $this->addCheck($checks, $errors, 'handoff_bundle_validation_handoff_bundle_text_hash_current', $this->hashMatches($handoffBundleTextFile, $handoffBundleValidationArtifacts['handoff_bundle_text_sha256'] ?? null), 'handoff bundle text hash matches current file');
            }
        }

        if (is_string($bundleStatusSummary)) {
            $this->addCheck($checks, $errors, 'bundle_status_summary_status', str_contains($bundleStatusSummary, 'sealed_3rc_validated'), 'summary contains sealed_3rc_validated');
        }

        if (is_string($handoffBundleText)) {
            $this->addCheck($checks, $errors, 'handoff_bundle_text_status', str_contains($handoffBundleText, 'Status: 3rc_handoff_bundle_ready'), 'handoff bundle text contains ready status');
        }

        $accepted = [] === $errors;
        $artifacts = [
            'manifest' => $this->artifact($manifestFile),
            'bundle_status' => $this->artifact($bundleStatusFile),
            'bundle_status_summary' => $this->artifact($bundleStatusSummaryFile),
            'handoff_bundle' => $this->artifact($handoffBundleFile),
            'handoff_bundle_text' => $this->artifact($handoffBundleTextFile),
            'handoff_bundle_validation' => $this->artifact($handoffBundleValidationFile),
            'terminal_status' => $this->artifact($terminalStatusFile),
            'terminal_status_summary' => $this->artifact($terminalStatusSummaryFile),
            'terminal_status_validation' => $this->artifact($terminalStatusValidationFile),
            'handoff_index' => $this->artifact($handoffIndexFile),
            'handoff_index_text' => $this->artifact($handoffIndexTextFile),
            'handoff_index_validation' => $this->artifact($handoffIndexValidationFile),
            'final_seal_validation' => $this->artifact($finalSealValidationFile),
        ];

        $result = [
            'schema_version' => '1.0',
            'component' => 'Administering',
            'package' => 'administering/admin',
            'namespace' => 'App\\Administering',
            'rc_stage' => '3RC-candidate',
            'status' => $accepted ? '3rc_handoff_accepted' : '3rc_handoff_blocked',
            'handoff_accepted' => $accepted,
            'accepted_meaning' => 'The Administering 3RC handoff bundle and bundle-aware terminal status are current, validated, and ready for owner/watchdog handoff.',
            'artifacts' => $artifacts,
            'checks' => $checks,
            'errors' => $errors,
        ];

        if (null !== $textFile) {
            $this->writeFile($textFile, $this->buildTextReceipt($result));
            $result['artifacts']['acceptance_text'] = $this->artifact($textFile);
        }

        if (null !== $outputFile) {
            $this->writeFile($outputFile, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
            $result['artifacts']['acceptance'] = $this->artifact($outputFile);
        }

        $encoded = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (false === $encoded) {
            throw new \RuntimeException('Unable to encode 3RC acceptance report.');
        }

        if (true === $input->getOption('json')) {
            $output->writeln($encoded);
        } elseif ($accepted) {
            $io->success('Administering 3RC handoff accepted.');
        } else {
            $io->error('Administering 3RC handoff is blocked.');
            foreach ($errors as $error) {
                $io->writeln('- '.$error);
            }
        }

        return $accepted ? Command::SUCCESS : Command::FAILURE;
    }

    private function pathOption(mixed $value): string
    {
        if (!is_string($value) || '' === trim($value)) {
            throw new \InvalidArgumentException('Expected a non-empty path option.');
        }

        return $value;
    }

    private function optionalPathOption(mixed $value): ?string
    {
        if (null === $value || false === $value) {
            return null;
        }

        if (!is_string($value) || '' === trim($value)) {
            throw new \InvalidArgumentException('Expected a non-empty optional path option.');
        }

        return $value;
    }

    /** @param list<array{name:string,passed:bool,details:string}> $checks @param list<string> $errors */
    private function readJson(string $path, array &$checks, array &$errors, string $name): ?array
    {
        $exists = is_file($path);
        $this->addCheck($checks, $errors, $name.'_file_exists', $exists, $path);
        if (!$exists) {
            return null;
        }

        $content = file_get_contents($path);
        if (false === $content) {
            $this->addCheck($checks, $errors, $name.'_file_readable', false, $path);

            return null;
        }

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            $this->addCheck($checks, $errors, $name.'_json_parse', false, $exception->getMessage());

            return null;
        }

        $this->addCheck($checks, $errors, $name.'_json_parse', is_array($decoded), $path);

        return is_array($decoded) ? $decoded : null;
    }

    /** @param list<array{name:string,passed:bool,details:string}> $checks @param list<string> $errors */
    private function readYaml(string $path, array &$checks, array &$errors, string $name): ?array
    {
        $exists = is_file($path);
        $this->addCheck($checks, $errors, $name.'_file_exists', $exists, $path);
        if (!$exists) {
            return null;
        }

        try {
            $decoded = Yaml::parseFile($path);
        } catch (\Throwable $exception) {
            $this->addCheck($checks, $errors, $name.'_yaml_parse', false, $exception->getMessage());

            return null;
        }

        $this->addCheck($checks, $errors, $name.'_yaml_parse', is_array($decoded), $path);

        return is_array($decoded) ? $decoded : null;
    }

    /** @param list<array{name:string,passed:bool,details:string}> $checks @param list<string> $errors */
    private function readText(string $path, array &$checks, array &$errors, string $name): ?string
    {
        $exists = is_file($path);
        $this->addCheck($checks, $errors, $name.'_file_exists', $exists, $path);
        if (!$exists) {
            return null;
        }

        $content = file_get_contents($path);
        $this->addCheck($checks, $errors, $name.'_file_readable', false !== $content, $path);

        return false === $content ? null : $content;
    }

    /** @param list<array{name:string,passed:bool,details:string}> $checks @param list<string> $errors */
    private function addCheck(array &$checks, array &$errors, string $name, bool $passed, string $details): void
    {
        $checks[] = [
            'name' => $name,
            'passed' => $passed,
            'details' => $details,
        ];

        if (!$passed) {
            $errors[] = sprintf('%s failed: %s', $name, $details);
        }
    }

    private function hashMatches(string $path, mixed $expectedHash): bool
    {
        return is_string($expectedHash)
            && '' !== $expectedHash
            && is_file($path)
            && hash_file('sha256', $path) === strtolower($expectedHash);
    }

    /** @return array{path:string,exists:bool,sha256:?string,bytes:?int} */
    private function artifact(string $path): array
    {
        if (!is_file($path)) {
            return [
                'path' => $path,
                'exists' => false,
                'sha256' => null,
                'bytes' => null,
            ];
        }

        return [
            'path' => $path,
            'exists' => true,
            'sha256' => hash_file('sha256', $path) ?: null,
            'bytes' => filesize($path) ?: 0,
        ];
    }

    /** @param array<string,mixed> $result */
    private function buildTextReceipt(array $result): string
    {
        $lines = [
            'Administering 3RC Acceptance',
            '============================',
            'Status: '.(string) $result['status'],
            'Accepted: '.(true === $result['handoff_accepted'] ? 'yes' : 'no'),
            'Component: Administering',
            'Package: administering/admin',
            'Namespace: App\\Administering',
            'RC stage: 3RC-candidate',
            '',
            'Artifacts:',
        ];

        $artifacts = $result['artifacts'] ?? [];
        if (is_array($artifacts)) {
            foreach ($artifacts as $name => $artifact) {
                if (!is_array($artifact)) {
                    continue;
                }
                $lines[] = sprintf('- %s: %s [%s]', (string) $name, (string) ($artifact['path'] ?? ''), (string) ($artifact['sha256'] ?? 'missing'));
            }
        }

        $errors = $result['errors'] ?? [];
        $lines[] = '';
        $lines[] = 'Errors:';
        if ([] === $errors) {
            $lines[] = '- none';
        } elseif (is_array($errors)) {
            foreach ($errors as $error) {
                $lines[] = '- '.(string) $error;
            }
        }

        return implode(PHP_EOL, $lines).PHP_EOL;
    }

    private function writeFile(string $path, string $content): void
    {
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Unable to create directory: %s', $directory));
        }

        if (false === file_put_contents($path, $content)) {
            throw new \RuntimeException(sprintf('Unable to write file: %s', $path));
        }
    }
}
