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
 * Builds the terminal, owner-facing Administering 3RC handoff index.
 *
 * This command is intentionally read-only with respect to Doctrine state. It
 * summarizes the final validated 3RC proof chain into a compact artifact that
 * can be attached to a handoff package or consumed by a watchdog without
 * manually traversing every intermediate proof JSON file.
 */
#[AsCommand(
    name: 'administering:rc:handoff-index',
    description: 'Builds the terminal Administering 3RC handoff index from validated final status artifacts.',
)]
final class AdministrationRcHandoffIndexCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('manifest-file', null, InputOption::VALUE_REQUIRED, 'Path to delivery/rc/manifest.yaml.', 'delivery/rc/manifest.yaml')
            ->addOption('final-status-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-final-status.json.', 'delivery/rc/runtime-proof-results/administering-rc-final-status.json')
            ->addOption('final-status-summary-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-final-status-summary.txt.', 'delivery/rc/runtime-proof-results/administering-rc-final-status-summary.txt')
            ->addOption('final-status-validation-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-final-status-validation.json.', 'delivery/rc/runtime-proof-results/administering-rc-final-status-validation.json')
            ->addOption('receipt-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-receipt.json.', 'delivery/rc/runtime-proof-results/administering-rc-receipt.json')
            ->addOption('receipt-text-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-receipt.txt.', 'delivery/rc/runtime-proof-results/administering-rc-receipt.txt')
            ->addOption('receipt-validation-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-receipt-validation.json.', 'delivery/rc/runtime-proof-results/administering-rc-receipt-validation.json')
            ->addOption('final-seal-validation-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-final-seal-validation.json.', 'delivery/rc/runtime-proof-results/administering-rc-final-seal-validation.json')
            ->addOption('output-file', null, InputOption::VALUE_REQUIRED, 'Optional path where the handoff index JSON should be written.')
            ->addOption('text-file', null, InputOption::VALUE_REQUIRED, 'Optional path where a compact handoff index text file should be written.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit the handoff index as JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $manifestFile = $this->pathOption($input->getOption('manifest-file'));
        $finalStatusFile = $this->pathOption($input->getOption('final-status-file'));
        $finalStatusSummaryFile = $this->pathOption($input->getOption('final-status-summary-file'));
        $finalStatusValidationFile = $this->pathOption($input->getOption('final-status-validation-file'));
        $receiptFile = $this->pathOption($input->getOption('receipt-file'));
        $receiptTextFile = $this->pathOption($input->getOption('receipt-text-file'));
        $receiptValidationFile = $this->pathOption($input->getOption('receipt-validation-file'));
        $finalSealValidationFile = $this->pathOption($input->getOption('final-seal-validation-file'));
        $outputFile = $this->optionalPathOption($input->getOption('output-file'));
        $textFile = $this->optionalPathOption($input->getOption('text-file'));

        $checks = [];
        $errors = [];

        $manifest = $this->readYaml($manifestFile, $checks, $errors, 'manifest');
        $finalStatus = $this->readJson($finalStatusFile, $checks, $errors, 'final_status');
        $finalStatusValidation = $this->readJson($finalStatusValidationFile, $checks, $errors, 'final_status_validation');
        $receipt = $this->readJson($receiptFile, $checks, $errors, 'receipt');
        $receiptValidation = $this->readJson($receiptValidationFile, $checks, $errors, 'receipt_validation');
        $finalSealValidation = $this->readJson($finalSealValidationFile, $checks, $errors, 'final_seal_validation');
        $finalStatusSummary = $this->readText($finalStatusSummaryFile, $checks, $errors, 'final_status_summary');
        $receiptText = $this->readText($receiptTextFile, $checks, $errors, 'receipt_text');

        if (is_array($manifest)) {
            $this->addCheck($checks, $errors, 'manifest_component', 'Administering' === ($manifest['component'] ?? null), 'component=Administering');
            $this->addCheck($checks, $errors, 'manifest_package', 'administering/admin' === ($manifest['package'] ?? null), 'package=administering/admin');
            $this->addCheck($checks, $errors, 'manifest_namespace', 'App\\Administering' === ($manifest['namespace'] ?? null), 'namespace=App\\Administering');
            $this->addCheck($checks, $errors, 'manifest_rc_stage', '3RC-candidate' === ($manifest['rc_stage'] ?? null), 'rc_stage=3RC-candidate');
            $this->addCheck($checks, $errors, 'manifest_handoff_index_artifact', isset($manifest['artifacts']['rc_handoff_index']), 'artifacts.rc_handoff_index exists');
            $this->addCheck($checks, $errors, 'manifest_handoff_index_text_artifact', isset($manifest['artifacts']['rc_handoff_index_text']), 'artifacts.rc_handoff_index_text exists');
        }

        $this->assertStatus($finalStatus, 'final_status', 'sealed_3rc_validated', 'sealed_3rc_validated', $checks, $errors);
        $this->assertStatus($finalStatusValidation, 'final_status_validation', 'final_status_valid', 'final_status_valid', $checks, $errors);
        $this->assertStatus($receipt, 'receipt', '3rc_receipt_ready', 'receipt_ready', $checks, $errors);
        $this->assertStatus($receiptValidation, 'receipt_validation', '3rc_receipt_valid', 'receipt_valid', $checks, $errors);
        $this->assertStatus($finalSealValidation, 'final_seal_validation', 'final_seal_valid', 'final_seal_valid', $checks, $errors);

        if (is_string($finalStatusSummary)) {
            $this->addCheck($checks, $errors, 'final_status_summary_contains_status', str_contains($finalStatusSummary, 'Status: sealed_3rc_validated'), 'summary contains terminal sealed status');
        }

        if (is_string($receiptText)) {
            $this->addCheck($checks, $errors, 'receipt_text_contains_status', str_contains($receiptText, '3rc_receipt_ready'), 'receipt text contains receipt status');
        }

        $hashes = [
            'manifest_sha256' => $this->hashOrNull($manifestFile),
            'final_status_sha256' => $this->hashOrNull($finalStatusFile),
            'final_status_summary_sha256' => $this->hashOrNull($finalStatusSummaryFile),
            'final_status_validation_sha256' => $this->hashOrNull($finalStatusValidationFile),
            'receipt_sha256' => $this->hashOrNull($receiptFile),
            'receipt_text_sha256' => $this->hashOrNull($receiptTextFile),
            'receipt_validation_sha256' => $this->hashOrNull($receiptValidationFile),
            'final_seal_validation_sha256' => $this->hashOrNull($finalSealValidationFile),
        ];

        if (is_array($finalStatusValidation)) {
            $artifacts = $finalStatusValidation['artifacts'] ?? null;
            $this->addCheck($checks, $errors, 'final_status_validation_artifacts_map', is_array($artifacts), 'final-status validation artifacts map exists');
            if (is_array($artifacts)) {
                foreach (['manifest_sha256', 'final_status_sha256', 'final_status_summary_sha256', 'receipt_sha256', 'receipt_text_sha256', 'receipt_validation_sha256', 'final_seal_validation_sha256'] as $key) {
                    $this->addCheck(
                        $checks,
                        $errors,
                        'final_status_validation_'.$key.'_current',
                        is_string($hashes[$key]) && $hashes[$key] === ($artifacts[$key] ?? null),
                        sprintf('%s matches current file', $key),
                    );
                }
            }
        }

        $ready = [] === $errors;
        $report = [
            'schema_version' => '1.0',
            'component' => 'Administering',
            'package' => 'administering/admin',
            'namespace' => 'App\\Administering',
            'rc_stage' => '3RC-candidate',
            'status' => $ready ? '3rc_handoff_index_ready' : 'blocked',
            'handoff_index_ready' => $ready,
            'generated_at_utc' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(\DateTimeInterface::ATOM),
            'terminal_artifacts' => [
                'manifest_file' => $manifestFile,
                'final_status_file' => $finalStatusFile,
                'final_status_summary_file' => $finalStatusSummaryFile,
                'final_status_validation_file' => $finalStatusValidationFile,
                'receipt_file' => $receiptFile,
                'receipt_text_file' => $receiptTextFile,
                'receipt_validation_file' => $receiptValidationFile,
                'final_seal_validation_file' => $finalSealValidationFile,
            ],
            'hashes' => $hashes,
            'owner_short_status' => [
                'status' => $ready ? 'sealed_3rc_validated' : 'blocked',
                'receipt' => is_array($receipt) ? ($receipt['status'] ?? null) : null,
                'receipt_validation' => is_array($receiptValidation) ? ($receiptValidation['status'] ?? null) : null,
                'final_status_validation' => is_array($finalStatusValidation) ? ($finalStatusValidation['status'] ?? null) : null,
            ],
            'checks' => $checks,
            'errors' => $errors,
        ];

        $encodedReport = (string) json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (null !== $outputFile) {
            $this->writeArtifact($outputFile, $encodedReport);
        }

        if (null !== $textFile) {
            $this->writeArtifact($textFile, $this->buildTextReport($report));
        }

        if ((bool) $input->getOption('json')) {
            $output->writeln($encodedReport);

            return $ready ? Command::SUCCESS : Command::FAILURE;
        }

        $io->title('Administering 3RC handoff index');
        $io->definitionList(
            ['status' => $report['status']],
            ['component' => $report['component']],
            ['output file' => $outputFile ?? '(not written)'],
            ['text file' => $textFile ?? '(not written)'],
        );

        if ([] !== $errors) {
            $io->error($errors);

            return Command::FAILURE;
        }

        $io->success('Administering 3RC handoff index is ready.');

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
        } catch (\Throwable $exception) {
            $this->addCheck($checks, $errors, $label.'_yaml_parseable', false, $exception->getMessage());

            return null;
        }

        $ok = is_array($decoded);
        $this->addCheck($checks, $errors, $label.'_yaml_parseable', $ok, $ok ? 'valid YAML mapping' : 'YAML root is not a mapping');

        return $ok ? $decoded : null;
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
        $content = file_get_contents($file);
        $ok = is_string($content);
        $this->addCheck($checks, $errors, $label.'_readable', $ok, $ok ? 'readable text' : 'file_get_contents failed');

        return $ok ? $content : null;
    }

    /**
     * @param array<string, mixed>|null                           $payload
     * @param list<array{name: string, ok: bool, detail: string}> $checks
     * @param list<string>                                        $errors
     */
    private function assertStatus(?array $payload, string $label, string $expectedStatus, string $booleanKey, array &$checks, array &$errors): void
    {
        if (null === $payload) {
            return;
        }

        $this->addCheck($checks, $errors, $label.'_status', $expectedStatus === ($payload['status'] ?? null), 'status='.$expectedStatus);
        $this->addCheck($checks, $errors, $label.'_boolean_true', true === ($payload[$booleanKey] ?? null), $booleanKey.'=true');
        $this->addCheck($checks, $errors, $label.'_errors_empty', [] === ($payload['errors'] ?? null), 'errors=[]');
    }

    /**
     * @param list<array{name: string, ok: bool, detail: string}> $checks
     * @param list<string>                                        $errors
     */
    private function addCheck(array &$checks, array &$errors, string $name, bool $ok, string $detail): void
    {
        $checks[] = [
            'name' => $name,
            'ok' => $ok,
            'detail' => $detail,
        ];

        if (!$ok) {
            $errors[] = $name.': '.$detail;
        }
    }

    private function hashOrNull(string $file): ?string
    {
        return is_file($file) ? hash_file('sha256', $file) : null;
    }

    private function writeArtifact(string $file, string $contents): void
    {
        $directory = dirname($file);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        file_put_contents($file, rtrim($contents).PHP_EOL);
    }

    /**
     * @param array<string, mixed> $report
     */
    private function buildTextReport(array $report): string
    {
        /** @var array<string, string|null> $hashes */
        $hashes = is_array($report['hashes'] ?? null) ? $report['hashes'] : [];
        /** @var array<string, mixed> $shortStatus */
        $shortStatus = is_array($report['owner_short_status'] ?? null) ? $report['owner_short_status'] : [];
        /** @var list<string> $errors */
        $errors = is_array($report['errors'] ?? null) ? $report['errors'] : [];

        $lines = [
            'Administering 3RC Handoff Index',
            'Status: '.(string) ($report['status'] ?? 'unknown'),
            'Ready: '.(true === ($report['handoff_index_ready'] ?? null) ? 'yes' : 'no'),
            'Component: '.(string) ($report['component'] ?? 'unknown'),
            'Package: '.(string) ($report['package'] ?? 'unknown'),
            'Namespace: '.(string) ($report['namespace'] ?? 'unknown'),
            'RC stage: '.(string) ($report['rc_stage'] ?? 'unknown'),
            'Owner short status: '.(string) ($shortStatus['status'] ?? 'unknown'),
            'Receipt: '.(string) ($shortStatus['receipt'] ?? 'unknown'),
            'Receipt validation: '.(string) ($shortStatus['receipt_validation'] ?? 'unknown'),
            'Final status validation: '.(string) ($shortStatus['final_status_validation'] ?? 'unknown'),
            '',
            'Hashes:',
        ];

        foreach ($hashes as $name => $hash) {
            $lines[] = sprintf('- %s: %s', $name, $hash ?? '(missing)');
        }

        $lines[] = '';
        $lines[] = 'Errors:';
        if ([] === $errors) {
            $lines[] = '- none';
        } else {
            foreach ($errors as $error) {
                $lines[] = '- '.$error;
            }
        }

        return implode(PHP_EOL, $lines);
    }
}
