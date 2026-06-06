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
 * Validates the terminal Administering 3RC status artifact.
 *
 * The final status is the compact owner/watchdog entry point after the receipt
 * has also been validated. This command is the after-the-fact guard for that
 * terminal file: it proves the final status JSON and summary still match the
 * current receipt, receipt-validation, final-seal-validation, and manifest
 * files instead of trusting a stale status artifact.
 */
#[AsCommand(
    name: 'administering:rc:final-status:validate',
    description: 'Validates the terminal Administering 3RC status artifact against current receipt and seal artifacts.',
)]
final class AdministrationRcFinalStatusValidateCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('manifest-file', null, InputOption::VALUE_REQUIRED, 'Path to delivery/rc/manifest.yaml.', 'delivery/rc/manifest.yaml')
            ->addOption('final-status-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-final-status.json.', 'delivery/rc/runtime-proof-results/administering-rc-final-status.json')
            ->addOption('final-status-summary-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-final-status-summary.txt.', 'delivery/rc/runtime-proof-results/administering-rc-final-status-summary.txt')
            ->addOption('receipt-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-receipt.json.', 'delivery/rc/runtime-proof-results/administering-rc-receipt.json')
            ->addOption('receipt-text-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-receipt.txt.', 'delivery/rc/runtime-proof-results/administering-rc-receipt.txt')
            ->addOption('receipt-validation-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-receipt-validation.json.', 'delivery/rc/runtime-proof-results/administering-rc-receipt-validation.json')
            ->addOption('final-seal-validation-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-final-seal-validation.json.', 'delivery/rc/runtime-proof-results/administering-rc-final-seal-validation.json')
            ->addOption('output-file', null, InputOption::VALUE_REQUIRED, 'Optional path where the terminal status validation JSON should be written.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit the validation report as JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $manifestFile = $this->pathOption($input->getOption('manifest-file'));
        $finalStatusFile = $this->pathOption($input->getOption('final-status-file'));
        $finalStatusSummaryFile = $this->pathOption($input->getOption('final-status-summary-file'));
        $receiptFile = $this->pathOption($input->getOption('receipt-file'));
        $receiptTextFile = $this->pathOption($input->getOption('receipt-text-file'));
        $receiptValidationFile = $this->pathOption($input->getOption('receipt-validation-file'));
        $finalSealValidationFile = $this->pathOption($input->getOption('final-seal-validation-file'));
        $outputFile = $this->optionalPathOption($input->getOption('output-file'));

        $checks = [];
        $errors = [];

        $manifest = $this->readYaml($manifestFile, $checks, $errors, 'manifest');
        $finalStatus = $this->readJson($finalStatusFile, $checks, $errors, 'final_status');
        $receipt = $this->readJson($receiptFile, $checks, $errors, 'receipt');
        $receiptValidation = $this->readJson($receiptValidationFile, $checks, $errors, 'receipt_validation');
        $finalSealValidation = $this->readJson($finalSealValidationFile, $checks, $errors, 'final_seal_validation');
        $finalStatusSummary = $this->readText($finalStatusSummaryFile, $checks, $errors, 'final_status_summary');
        $this->addCheck($checks, $errors, 'receipt_text_file_exists', is_file($receiptTextFile), $receiptTextFile);

        if (is_array($manifest)) {
            $this->addCheck($checks, $errors, 'manifest_component', 'Administering' === ($manifest['component'] ?? null), 'component=Administering');
            $this->addCheck($checks, $errors, 'manifest_package', 'administering/admin' === ($manifest['package'] ?? null), 'package=administering/admin');
            $this->addCheck($checks, $errors, 'manifest_namespace', 'App\\Administering' === ($manifest['namespace'] ?? null), 'namespace=App\\Administering');
            $this->addCheck($checks, $errors, 'manifest_rc_stage', '3RC-candidate' === ($manifest['rc_stage'] ?? null), 'rc_stage=3RC-candidate');
            $this->addCheck($checks, $errors, 'manifest_final_status_artifact', isset($manifest['artifacts']['rc_final_status']), 'artifacts.rc_final_status exists');
            $this->addCheck($checks, $errors, 'manifest_final_status_validation_artifact', isset($manifest['artifacts']['rc_final_status_validation']), 'artifacts.rc_final_status_validation exists');
        }

        if (is_array($receipt)) {
            $this->addCheck($checks, $errors, 'receipt_status', '3rc_receipt_ready' === ($receipt['status'] ?? null), 'status=3rc_receipt_ready');
            $this->addCheck($checks, $errors, 'receipt_ready_true', true === ($receipt['receipt_ready'] ?? null), 'receipt_ready=true');
            $this->addCheck($checks, $errors, 'receipt_errors_empty', [] === ($receipt['errors'] ?? null), 'errors=[]');
        }

        if (is_array($receiptValidation)) {
            $this->addCheck($checks, $errors, 'receipt_validation_status', '3rc_receipt_valid' === ($receiptValidation['status'] ?? null), 'status=3rc_receipt_valid');
            $this->addCheck($checks, $errors, 'receipt_validation_true', true === ($receiptValidation['receipt_valid'] ?? null), 'receipt_valid=true');
            $this->addCheck($checks, $errors, 'receipt_validation_errors_empty', [] === ($receiptValidation['errors'] ?? null), 'errors=[]');
        }

        if (is_array($finalSealValidation)) {
            $this->addCheck($checks, $errors, 'final_seal_validation_status', 'final_seal_valid' === ($finalSealValidation['status'] ?? null), 'status=final_seal_valid');
            $this->addCheck($checks, $errors, 'final_seal_validation_true', true === ($finalSealValidation['final_seal_valid'] ?? null), 'final_seal_valid=true');
            $this->addCheck($checks, $errors, 'final_seal_validation_errors_empty', [] === ($finalSealValidation['errors'] ?? null), 'errors=[]');
        }

        $currentHashes = [
            'manifest_sha256' => $this->hashOrNull($manifestFile),
            'receipt_sha256' => $this->hashOrNull($receiptFile),
            'receipt_text_sha256' => $this->hashOrNull($receiptTextFile),
            'receipt_validation_sha256' => $this->hashOrNull($receiptValidationFile),
            'final_seal_validation_sha256' => $this->hashOrNull($finalSealValidationFile),
            'final_status_sha256' => $this->hashOrNull($finalStatusFile),
            'final_status_summary_sha256' => $this->hashOrNull($finalStatusSummaryFile),
        ];

        if (is_array($finalStatus)) {
            $this->addCheck($checks, $errors, 'final_status_status', 'sealed_3rc_validated' === ($finalStatus['status'] ?? null), 'status=sealed_3rc_validated');
            $this->addCheck($checks, $errors, 'final_status_boolean_true', true === ($finalStatus['sealed_3rc_validated'] ?? null), 'sealed_3rc_validated=true');
            $this->addCheck($checks, $errors, 'final_status_include_receipt_artifacts', true === ($finalStatus['include_receipt_artifacts'] ?? null), 'include_receipt_artifacts=true');
            $this->addCheck($checks, $errors, 'final_status_errors_empty', [] === ($finalStatus['errors'] ?? null), 'errors=[]');

            $artifactStatus = $finalStatus['artifact_status'] ?? null;
            $this->addCheck($checks, $errors, 'final_status_artifact_status_map', is_array($artifactStatus), 'artifact_status map exists');
            if (is_array($artifactStatus)) {
                $this->addCheck($checks, $errors, 'final_status_receipt_status', '3rc_receipt_ready' === ($artifactStatus['receipt'] ?? null), 'artifact_status.receipt=3rc_receipt_ready');
                $this->addCheck($checks, $errors, 'final_status_receipt_validation_status', '3rc_receipt_valid' === ($artifactStatus['receipt_validation'] ?? null), 'artifact_status.receipt_validation=3rc_receipt_valid');
                $this->addCheck($checks, $errors, 'final_status_final_seal_validation_status', 'final_seal_valid' === ($artifactStatus['final_seal_validation'] ?? null), 'artifact_status.final_seal_validation=final_seal_valid');
            }

            $artifacts = $finalStatus['artifacts'] ?? null;
            $this->addCheck($checks, $errors, 'final_status_artifacts_map', is_array($artifacts), 'artifacts map exists');
            if (is_array($artifacts)) {
                foreach ($currentHashes as $name => $currentHash) {
                    if ('final_status_sha256' === $name || 'final_status_summary_sha256' === $name) {
                        continue;
                    }

                    $this->addCheck(
                        $checks,
                        $errors,
                        sprintf('final_status_%s_current', $name),
                        is_string($currentHash) && $currentHash === ($artifacts[$name] ?? null),
                        sprintf('%s matches current file', $name),
                    );
                }
            }
        }

        if (is_string($finalStatusSummary)) {
            $this->addCheck($checks, $errors, 'final_status_summary_contains_status', str_contains($finalStatusSummary, 'Status: sealed_3rc_validated'), 'summary contains sealed status');
            $receiptValidationHash = $currentHashes['receipt_validation_sha256'];
            $this->addCheck($checks, $errors, 'final_status_summary_contains_receipt_validation_hash', null !== $receiptValidationHash && str_contains($finalStatusSummary, $receiptValidationHash), 'summary contains receipt-validation SHA-256');
        }

        $valid = [] === $errors;
        $report = [
            'schema_version' => '1.0',
            'component' => 'Administering',
            'package' => 'administering/admin',
            'namespace' => 'App\\Administering',
            'rc_stage' => '3RC-candidate',
            'status' => $valid ? 'final_status_valid' : 'blocked',
            'final_status_valid' => $valid,
            'validated_at_utc' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(\DateTimeInterface::ATOM),
            'artifacts' => [
                'manifest_file' => $manifestFile,
                'final_status_file' => $finalStatusFile,
                'final_status_summary_file' => $finalStatusSummaryFile,
                'receipt_file' => $receiptFile,
                'receipt_text_file' => $receiptTextFile,
                'receipt_validation_file' => $receiptValidationFile,
                'final_seal_validation_file' => $finalSealValidationFile,
                'manifest_sha256' => $currentHashes['manifest_sha256'],
                'final_status_sha256' => $currentHashes['final_status_sha256'],
                'final_status_summary_sha256' => $currentHashes['final_status_summary_sha256'],
                'receipt_sha256' => $currentHashes['receipt_sha256'],
                'receipt_text_sha256' => $currentHashes['receipt_text_sha256'],
                'receipt_validation_sha256' => $currentHashes['receipt_validation_sha256'],
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

        $io->title('Administering 3RC final status validation');
        $io->definitionList(
            ['status' => $report['status']],
            ['component' => $report['component']],
            ['final status' => $finalStatusFile],
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
    private function addCheck(array &$checks, array &$errors, string $name, bool $ok, string $detail): void
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
