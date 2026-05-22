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
 * Validates the compact 3RC receipt after it has been generated.
 *
 * This command is intentionally read-only. It lets owner/watchdog intake verify
 * that the short receipt still corresponds to the current status, status summary,
 * final-seal validation, and RC manifest files without manually inspecting the
 * full proof chain.
 */
#[AsCommand(
    name: 'administering:rc:receipt:validate',
    description: 'Validates the Administering 3RC owner receipt against current RC artifacts.',
)]
final class AdministrationRcReceiptValidateCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('manifest-file', null, InputOption::VALUE_REQUIRED, 'Path to delivery/rc/manifest.yaml.', 'delivery/rc/manifest.yaml')
            ->addOption('status-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-status.json.', 'delivery/rc/runtime-proof-results/administering-rc-status.json')
            ->addOption('status-summary-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-status-summary.txt.', 'delivery/rc/runtime-proof-results/administering-rc-status-summary.txt')
            ->addOption('final-seal-validation-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-final-seal-validation.json.', 'delivery/rc/runtime-proof-results/administering-rc-final-seal-validation.json')
            ->addOption('receipt-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-receipt.json.', 'delivery/rc/runtime-proof-results/administering-rc-receipt.json')
            ->addOption('receipt-text-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-receipt.txt.', 'delivery/rc/runtime-proof-results/administering-rc-receipt.txt')
            ->addOption('output-file', null, InputOption::VALUE_REQUIRED, 'Optional path where the receipt validation JSON should be written.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit the validation report as JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $manifestFile = $this->pathOption($input->getOption('manifest-file'));
        $statusFile = $this->pathOption($input->getOption('status-file'));
        $statusSummaryFile = $this->pathOption($input->getOption('status-summary-file'));
        $finalSealValidationFile = $this->pathOption($input->getOption('final-seal-validation-file'));
        $receiptFile = $this->pathOption($input->getOption('receipt-file'));
        $receiptTextFile = $this->pathOption($input->getOption('receipt-text-file'));
        $outputFile = $this->optionalPathOption($input->getOption('output-file'));

        $checks = [];
        $errors = [];

        $manifest = $this->readYaml($manifestFile, $checks, $errors, 'manifest');
        $status = $this->readJson($statusFile, $checks, $errors, 'status');
        $finalSealValidation = $this->readJson($finalSealValidationFile, $checks, $errors, 'final_seal_validation');
        $receipt = $this->readJson($receiptFile, $checks, $errors, 'receipt');
        $receiptText = $this->readText($receiptTextFile, $checks, $errors, 'receipt_text');
        $this->addCheck($checks, $errors, 'status_summary_file_exists', is_file($statusSummaryFile), $statusSummaryFile);

        if (is_array($manifest)) {
            $this->addCheck($checks, $errors, 'manifest_component', 'Administering' === ($manifest['component'] ?? null), 'component=Administering');
            $this->addCheck($checks, $errors, 'manifest_package', 'administering/admin' === ($manifest['package'] ?? null), 'package=administering/admin');
            $this->addCheck($checks, $errors, 'manifest_namespace', 'App\\Administering' === ($manifest['namespace'] ?? null), 'namespace=App\\Administering');
            $this->addCheck($checks, $errors, 'manifest_rc_stage', '3RC-candidate' === ($manifest['rc_stage'] ?? null), 'rc_stage=3RC-candidate');
            $this->addCheck($checks, $errors, 'manifest_receipt_validation_artifact', isset($manifest['artifacts']['rc_receipt_validation']), 'artifacts.rc_receipt_validation exists');
        }

        if (is_array($status)) {
            $this->addCheck($checks, $errors, 'status_sealed_3rc_validated', 'sealed_3rc_validated' === ($status['status'] ?? null), 'status=sealed_3rc_validated');
            $this->addCheck($checks, $errors, 'status_boolean_true', true === ($status['sealed_3rc_validated'] ?? null), 'sealed_3rc_validated=true');
            $this->addCheck($checks, $errors, 'status_errors_empty', [] === ($status['errors'] ?? null), 'errors=[]');
        }

        if (is_array($finalSealValidation)) {
            $this->addCheck($checks, $errors, 'final_seal_validation_status', 'final_seal_valid' === ($finalSealValidation['status'] ?? null), 'status=final_seal_valid');
            $this->addCheck($checks, $errors, 'final_seal_validation_boolean_true', true === ($finalSealValidation['final_seal_valid'] ?? null), 'final_seal_valid=true');
            $this->addCheck($checks, $errors, 'final_seal_validation_errors_empty', [] === ($finalSealValidation['errors'] ?? null), 'errors=[]');
        }

        $sourceHashes = [
            'manifest_sha256' => $this->hashOrNull($manifestFile),
            'status_sha256' => $this->hashOrNull($statusFile),
            'status_summary_sha256' => $this->hashOrNull($statusSummaryFile),
            'final_seal_validation_sha256' => $this->hashOrNull($finalSealValidationFile),
        ];

        if (is_array($receipt)) {
            $this->addCheck($checks, $errors, 'receipt_status', '3rc_receipt_ready' === ($receipt['status'] ?? null), 'status=3rc_receipt_ready');
            $this->addCheck($checks, $errors, 'receipt_boolean_true', true === ($receipt['receipt_ready'] ?? null), 'receipt_ready=true');
            $this->addCheck($checks, $errors, 'receipt_errors_empty', [] === ($receipt['errors'] ?? null), 'errors=[]');
            $this->addCheck($checks, $errors, 'receipt_sealed_status', 'sealed_3rc_validated' === ($receipt['sealed_status'] ?? null), 'sealed_status=sealed_3rc_validated');
            $this->addCheck($checks, $errors, 'receipt_final_seal_validation_status', 'final_seal_valid' === ($receipt['final_seal_validation_status'] ?? null), 'final_seal_validation_status=final_seal_valid');

            $sourceArtifacts = $receipt['source_artifacts'] ?? null;
            $this->addCheck($checks, $errors, 'receipt_source_artifacts_present', is_array($sourceArtifacts), 'receipt.source_artifacts map exists');
            if (is_array($sourceArtifacts)) {
                foreach ($sourceHashes as $hashName => $currentHash) {
                    $this->addCheck(
                        $checks,
                        $errors,
                        sprintf('receipt_%s_current', $hashName),
                        is_string($currentHash) && $currentHash === ($sourceArtifacts[$hashName] ?? null),
                        sprintf('%s matches current file', $hashName),
                    );
                }
            }

            $fingerprintSource = json_encode($sourceHashes, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $this->addCheck(
                $checks,
                $errors,
                'receipt_fingerprint_current',
                hash('sha256', $fingerprintSource) === ($receipt['receipt_fingerprint'] ?? null),
                'receipt fingerprint matches current source artifact hashes',
            );
        }

        if (is_string($receiptText)) {
            $this->addCheck($checks, $errors, 'receipt_text_contains_status', str_contains($receiptText, 'Status: 3rc_receipt_ready'), 'receipt text contains ready status');
            $this->addCheck($checks, $errors, 'receipt_text_contains_fingerprint', !is_array($receipt) || !is_string($receipt['receipt_fingerprint'] ?? null) || str_contains($receiptText, $receipt['receipt_fingerprint']), 'receipt text contains receipt fingerprint');
        }

        $valid = [] === $errors;
        $report = [
            'schema_version' => '1.0',
            'component' => 'Administering',
            'package' => 'administering/admin',
            'namespace' => 'App\\Administering',
            'rc_stage' => '3RC-candidate',
            'status' => $valid ? '3rc_receipt_valid' : 'blocked',
            'receipt_valid' => $valid,
            'validated_at_utc' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(\DateTimeInterface::ATOM),
            'artifacts' => [
                'manifest_file' => $manifestFile,
                'status_file' => $statusFile,
                'status_summary_file' => $statusSummaryFile,
                'final_seal_validation_file' => $finalSealValidationFile,
                'receipt_file' => $receiptFile,
                'receipt_text_file' => $receiptTextFile,
                'manifest_sha256' => $sourceHashes['manifest_sha256'],
                'status_sha256' => $sourceHashes['status_sha256'],
                'status_summary_sha256' => $sourceHashes['status_summary_sha256'],
                'final_seal_validation_sha256' => $sourceHashes['final_seal_validation_sha256'],
                'receipt_sha256' => $this->hashOrNull($receiptFile),
                'receipt_text_sha256' => $this->hashOrNull($receiptTextFile),
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

        $io->title('Administering 3RC receipt validation');
        $io->definitionList(
            ['status' => $report['status']],
            ['receipt valid' => $valid ? 'yes' : 'no'],
            ['output file' => $outputFile ?? '(not written)'],
        );

        if ([] !== $errors) {
            $io->error($errors);

            return Command::FAILURE;
        }

        $io->success('Administering 3RC receipt is valid against current artifacts.');

        return Command::SUCCESS;
    }

    private function pathOption(mixed $value): string
    {
        $path = is_string($value) ? trim($value) : '';
        if ('' === $path) {
            throw new \InvalidArgumentException('Required path option cannot be empty.');
        }

        return $path;
    }

    private function optionalPathOption(mixed $value): ?string
    {
        $path = is_string($value) ? trim($value) : '';

        return '' === $path ? null : $path;
    }

    /** @param list<array<string, mixed>> $checks @param list<string> $errors */
    private function readYaml(string $path, array &$checks, array &$errors, string $name): ?array
    {
        $this->addCheck($checks, $errors, sprintf('%s_file_exists', $name), is_file($path), $path);
        if (!is_file($path)) {
            return null;
        }

        try {
            $value = Yaml::parseFile($path);
        } catch (\Throwable $exception) {
            $this->addCheck($checks, $errors, sprintf('%s_yaml_parseable', $name), false, $exception->getMessage());

            return null;
        }

        $this->addCheck($checks, $errors, sprintf('%s_yaml_parseable', $name), is_array($value), 'YAML root is a map');

        return is_array($value) ? $value : null;
    }

    /** @param list<array<string, mixed>> $checks @param list<string> $errors */
    private function readJson(string $path, array &$checks, array &$errors, string $name): ?array
    {
        $this->addCheck($checks, $errors, sprintf('%s_file_exists', $name), is_file($path), $path);
        if (!is_file($path)) {
            return null;
        }

        try {
            $value = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $exception) {
            $this->addCheck($checks, $errors, sprintf('%s_json_parseable', $name), false, $exception->getMessage());

            return null;
        }

        $this->addCheck($checks, $errors, sprintf('%s_json_parseable', $name), is_array($value), 'JSON root is an object');

        return is_array($value) ? $value : null;
    }

    /** @param list<array<string, mixed>> $checks @param list<string> $errors */
    private function readText(string $path, array &$checks, array &$errors, string $name): ?string
    {
        $this->addCheck($checks, $errors, sprintf('%s_file_exists', $name), is_file($path), $path);
        if (!is_file($path)) {
            return null;
        }

        $content = file_get_contents($path);
        $this->addCheck($checks, $errors, sprintf('%s_readable', $name), false !== $content, 'text file readable');

        return false === $content ? null : (string) $content;
    }

    /** @param list<array<string, mixed>> $checks @param list<string> $errors */
    private function addCheck(array &$checks, array &$errors, string $name, bool $passed, string $detail): void
    {
        $checks[] = [
            'name' => $name,
            'passed' => $passed,
            'detail' => $detail,
        ];

        if (!$passed) {
            $errors[] = sprintf('%s failed: %s', $name, $detail);
        }
    }

    private function hashOrNull(string $path): ?string
    {
        if (!is_file($path)) {
            return null;
        }

        $hash = hash_file('sha256', $path);

        return false === $hash ? null : $hash;
    }

    private function writeArtifact(string $path, string $payload): void
    {
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Unable to create directory: %s', $directory));
        }

        if (false === file_put_contents($path, $payload."\n")) {
            throw new \RuntimeException(sprintf('Unable to write artifact: %s', $path));
        }
    }
}
