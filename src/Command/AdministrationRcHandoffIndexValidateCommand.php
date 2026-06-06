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
 * Validates the terminal Administering 3RC handoff index artifact.
 *
 * This is the after-the-fact guard for the owner-facing handoff index. It
 * proves that the compact handoff JSON/text still match the current terminal
 * final-status, receipt, final-seal-validation, and manifest files instead of
 * trusting a stale handoff artifact.
 */
#[AsCommand(
    name: 'administering:rc:handoff-index:validate',
    description: 'Validates the terminal Administering 3RC handoff index against current final-status and receipt artifacts.',
)]
final class AdministrationRcHandoffIndexValidateCommand extends Command
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
            ->addOption('handoff-index-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-handoff-index.json.', 'delivery/rc/runtime-proof-results/administering-rc-handoff-index.json')
            ->addOption('handoff-index-text-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-handoff-index.txt.', 'delivery/rc/runtime-proof-results/administering-rc-handoff-index.txt')
            ->addOption('output-file', null, InputOption::VALUE_REQUIRED, 'Optional path where the handoff index validation JSON should be written.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit the validation report as JSON.');
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
        $handoffIndexFile = $this->pathOption($input->getOption('handoff-index-file'));
        $handoffIndexTextFile = $this->pathOption($input->getOption('handoff-index-text-file'));
        $outputFile = $this->optionalPathOption($input->getOption('output-file'));

        $checks = [];
        $errors = [];

        $manifest = $this->readYaml($manifestFile, $checks, $errors, 'manifest');
        $finalStatus = $this->readJson($finalStatusFile, $checks, $errors, 'final_status');
        $finalStatusValidation = $this->readJson($finalStatusValidationFile, $checks, $errors, 'final_status_validation');
        $receipt = $this->readJson($receiptFile, $checks, $errors, 'receipt');
        $receiptValidation = $this->readJson($receiptValidationFile, $checks, $errors, 'receipt_validation');
        $finalSealValidation = $this->readJson($finalSealValidationFile, $checks, $errors, 'final_seal_validation');
        $handoffIndex = $this->readJson($handoffIndexFile, $checks, $errors, 'handoff_index');
        $finalStatusSummary = $this->readText($finalStatusSummaryFile, $checks, $errors, 'final_status_summary');
        $receiptText = $this->readText($receiptTextFile, $checks, $errors, 'receipt_text');
        $handoffIndexText = $this->readText($handoffIndexTextFile, $checks, $errors, 'handoff_index_text');

        if (is_array($manifest)) {
            $this->addCheck($checks, $errors, 'manifest_component', 'Administering' === ($manifest['component'] ?? null), 'component=Administering');
            $this->addCheck($checks, $errors, 'manifest_package', 'administering/admin' === ($manifest['package'] ?? null), 'package=administering/admin');
            $this->addCheck($checks, $errors, 'manifest_namespace', 'App\\Administering' === ($manifest['namespace'] ?? null), 'namespace=App\\Administering');
            $this->addCheck($checks, $errors, 'manifest_rc_stage', '3RC-candidate' === ($manifest['rc_stage'] ?? null), 'rc_stage=3RC-candidate');
            $this->addCheck($checks, $errors, 'manifest_handoff_index_artifact', isset($manifest['artifacts']['rc_handoff_index']), 'artifacts.rc_handoff_index exists');
            $this->addCheck($checks, $errors, 'manifest_handoff_index_text_artifact', isset($manifest['artifacts']['rc_handoff_index_text']), 'artifacts.rc_handoff_index_text exists');
            $this->addCheck($checks, $errors, 'manifest_handoff_index_validation_artifact', isset($manifest['artifacts']['rc_handoff_index_validation']), 'artifacts.rc_handoff_index_validation exists');
        }

        $this->assertStatus($finalStatus, 'final_status', 'sealed_3rc_validated', 'sealed_3rc_validated', $checks, $errors);
        $this->assertStatus($finalStatusValidation, 'final_status_validation', 'final_status_valid', 'final_status_valid', $checks, $errors);
        $this->assertStatus($receipt, 'receipt', '3rc_receipt_ready', 'receipt_ready', $checks, $errors);
        $this->assertStatus($receiptValidation, 'receipt_validation', '3rc_receipt_valid', 'receipt_valid', $checks, $errors);
        $this->assertStatus($finalSealValidation, 'final_seal_validation', 'final_seal_valid', 'final_seal_valid', $checks, $errors);

        $currentHashes = [
            'manifest_sha256' => $this->hashOrNull($manifestFile),
            'final_status_sha256' => $this->hashOrNull($finalStatusFile),
            'final_status_summary_sha256' => $this->hashOrNull($finalStatusSummaryFile),
            'final_status_validation_sha256' => $this->hashOrNull($finalStatusValidationFile),
            'receipt_sha256' => $this->hashOrNull($receiptFile),
            'receipt_text_sha256' => $this->hashOrNull($receiptTextFile),
            'receipt_validation_sha256' => $this->hashOrNull($receiptValidationFile),
            'final_seal_validation_sha256' => $this->hashOrNull($finalSealValidationFile),
            'handoff_index_sha256' => $this->hashOrNull($handoffIndexFile),
            'handoff_index_text_sha256' => $this->hashOrNull($handoffIndexTextFile),
        ];

        if (is_array($handoffIndex)) {
            $this->addCheck($checks, $errors, 'handoff_index_status', '3rc_handoff_index_ready' === ($handoffIndex['status'] ?? null), 'status=3rc_handoff_index_ready');
            $this->addCheck($checks, $errors, 'handoff_index_ready_true', true === ($handoffIndex['handoff_index_ready'] ?? null), 'handoff_index_ready=true');
            $this->addCheck($checks, $errors, 'handoff_index_errors_empty', [] === ($handoffIndex['errors'] ?? null), 'errors=[]');
            $this->addCheck($checks, $errors, 'handoff_index_component', 'Administering' === ($handoffIndex['component'] ?? null), 'component=Administering');
            $this->addCheck($checks, $errors, 'handoff_index_package', 'administering/admin' === ($handoffIndex['package'] ?? null), 'package=administering/admin');
            $this->addCheck($checks, $errors, 'handoff_index_namespace', 'App\\Administering' === ($handoffIndex['namespace'] ?? null), 'namespace=App\\Administering');
            $this->addCheck($checks, $errors, 'handoff_index_rc_stage', '3RC-candidate' === ($handoffIndex['rc_stage'] ?? null), 'rc_stage=3RC-candidate');

            $hashes = $handoffIndex['hashes'] ?? null;
            $this->addCheck($checks, $errors, 'handoff_index_hashes_map', is_array($hashes), 'hashes map exists');
            if (is_array($hashes)) {
                foreach (['manifest_sha256', 'final_status_sha256', 'final_status_summary_sha256', 'final_status_validation_sha256', 'receipt_sha256', 'receipt_text_sha256', 'receipt_validation_sha256', 'final_seal_validation_sha256'] as $key) {
                    $this->addCheck(
                        $checks,
                        $errors,
                        'handoff_index_'.$key.'_current',
                        is_string($currentHashes[$key]) && $currentHashes[$key] === ($hashes[$key] ?? null),
                        sprintf('%s matches current file', $key),
                    );
                }
            }
        }

        if (is_string($finalStatusSummary)) {
            $this->addCheck($checks, $errors, 'final_status_summary_contains_status', str_contains($finalStatusSummary, 'Status: sealed_3rc_validated'), 'summary contains sealed_3rc_validated');
        }

        if (is_string($receiptText)) {
            $this->addCheck($checks, $errors, 'receipt_text_contains_status', str_contains($receiptText, '3rc_receipt_ready'), 'receipt text contains receipt status');
        }

        if (is_string($handoffIndexText)) {
            $this->addCheck($checks, $errors, 'handoff_index_text_contains_status', str_contains($handoffIndexText, 'Status: 3rc_handoff_index_ready'), 'handoff index text contains handoff status');
        }

        $valid = [] === $errors;
        $report = [
            'schema_version' => '1.0',
            'component' => 'Administering',
            'package' => 'administering/admin',
            'namespace' => 'App\\Administering',
            'rc_stage' => '3RC-candidate',
            'status' => $valid ? '3rc_handoff_index_valid' : 'blocked',
            'handoff_index_valid' => $valid,
            'validated_at_utc' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(\DateTimeInterface::ATOM),
            'artifacts' => [
                'manifest_file' => $manifestFile,
                'final_status_file' => $finalStatusFile,
                'final_status_summary_file' => $finalStatusSummaryFile,
                'final_status_validation_file' => $finalStatusValidationFile,
                'receipt_file' => $receiptFile,
                'receipt_text_file' => $receiptTextFile,
                'receipt_validation_file' => $receiptValidationFile,
                'final_seal_validation_file' => $finalSealValidationFile,
                'handoff_index_file' => $handoffIndexFile,
                'handoff_index_text_file' => $handoffIndexTextFile,
                'manifest_sha256' => $currentHashes['manifest_sha256'],
                'final_status_sha256' => $currentHashes['final_status_sha256'],
                'final_status_summary_sha256' => $currentHashes['final_status_summary_sha256'],
                'final_status_validation_sha256' => $currentHashes['final_status_validation_sha256'],
                'receipt_sha256' => $currentHashes['receipt_sha256'],
                'receipt_text_sha256' => $currentHashes['receipt_text_sha256'],
                'receipt_validation_sha256' => $currentHashes['receipt_validation_sha256'],
                'final_seal_validation_sha256' => $currentHashes['final_seal_validation_sha256'],
                'handoff_index_sha256' => $currentHashes['handoff_index_sha256'],
                'handoff_index_text_sha256' => $currentHashes['handoff_index_text_sha256'],
            ],
            'checks' => $checks,
            'errors' => $errors,
        ];

        $encodedReport = (string) json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (null !== $outputFile) {
            $this->writeArtifact($outputFile, $encodedReport);
        }

        if ((bool) $input->getOption('json')) {
            $output->writeln($encodedReport);

            return $valid ? Command::SUCCESS : Command::FAILURE;
        }

        $io->title('Administering 3RC handoff index validation');
        $io->definitionList(
            ['status' => $report['status']],
            ['component' => $report['component']],
            ['handoff index' => $handoffIndexFile],
            ['output file' => $outputFile ?? '(not written)'],
        );

        if ([] !== $errors) {
            $io->error($errors);

            return Command::FAILURE;
        }

        $io->success('Administering terminal 3RC handoff index is valid and current.');

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
        if (!is_file($file)) {
            return null;
        }

        $hash = hash_file('sha256', $file);

        return false === $hash ? null : $hash;
    }

    private function writeArtifact(string $file, string $contents): void
    {
        $directory = dirname($file);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        file_put_contents($file, rtrim($contents).PHP_EOL);
    }
}
