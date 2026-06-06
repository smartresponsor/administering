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
 * Builds a compact owner-facing receipt after the full Administering 3RC chain.
 *
 * The receipt intentionally does not rerun proof. It verifies the already captured
 * 3RC status and final-seal validation artifacts, records current SHA-256 hashes,
 * and emits a small JSON/text pair suitable for handoff intake.
 */
#[AsCommand(
    name: 'administering:rc:receipt',
    description: 'Builds a compact Administering 3RC owner receipt from validated RC status artifacts.',
)]
final class AdministrationRcReceiptCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('manifest-file', null, InputOption::VALUE_REQUIRED, 'Path to delivery/rc/manifest.yaml.', 'delivery/rc/manifest.yaml')
            ->addOption('status-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-status.json.', 'delivery/rc/runtime-proof-results/administering-rc-status.json')
            ->addOption('status-summary-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-status-summary.txt.', 'delivery/rc/runtime-proof-results/administering-rc-status-summary.txt')
            ->addOption('final-seal-validation-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-final-seal-validation.json.', 'delivery/rc/runtime-proof-results/administering-rc-final-seal-validation.json')
            ->addOption('output-file', null, InputOption::VALUE_REQUIRED, 'Optional path where the RC receipt JSON should be written.')
            ->addOption('text-file', null, InputOption::VALUE_REQUIRED, 'Optional path where the compact RC receipt text should be written.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit the receipt as JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $manifestFile = $this->pathOption($input->getOption('manifest-file'));
        $statusFile = $this->pathOption($input->getOption('status-file'));
        $statusSummaryFile = $this->pathOption($input->getOption('status-summary-file'));
        $finalSealValidationFile = $this->pathOption($input->getOption('final-seal-validation-file'));
        $outputFile = $this->optionalPathOption($input->getOption('output-file'));
        $textFile = $this->optionalPathOption($input->getOption('text-file'));

        $checks = [];
        $errors = [];

        $manifest = $this->readYaml($manifestFile, $checks, $errors, 'manifest');
        $status = $this->readJson($statusFile, $checks, $errors, 'status');
        $finalSealValidation = $this->readJson($finalSealValidationFile, $checks, $errors, 'final_seal_validation');
        $this->addCheck($checks, $errors, 'status_summary_file_exists', is_file($statusSummaryFile), $statusSummaryFile);

        if (is_array($manifest)) {
            $this->addCheck($checks, $errors, 'manifest_component', 'Administering' === ($manifest['component'] ?? null), 'component=Administering');
            $this->addCheck($checks, $errors, 'manifest_package', 'administering/admin' === ($manifest['package'] ?? null), 'package=administering/admin');
            $this->addCheck($checks, $errors, 'manifest_namespace', 'App\\Administering' === ($manifest['namespace'] ?? null), 'namespace=App\\Administering');
            $this->addCheck($checks, $errors, 'manifest_rc_stage', '3RC-candidate' === ($manifest['rc_stage'] ?? null), 'rc_stage=3RC-candidate');
            $this->addCheck($checks, $errors, 'manifest_receipt_artifact', isset($manifest['artifacts']['rc_receipt']), 'artifacts.rc_receipt exists');
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

        $ready = [] === $errors;
        $fingerprintSource = json_encode($sourceHashes, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $receipt = [
            'schema_version' => '1.0',
            'component' => 'Administering',
            'package' => 'administering/admin',
            'namespace' => 'App\\Administering',
            'rc_stage' => '3RC-candidate',
            'status' => $ready ? '3rc_receipt_ready' : 'blocked',
            'receipt_ready' => $ready,
            'sealed_status' => is_array($status) ? ($status['status'] ?? null) : null,
            'final_seal_validation_status' => is_array($finalSealValidation) ? ($finalSealValidation['status'] ?? null) : null,
            'receipt_fingerprint' => hash('sha256', $fingerprintSource),
            'reported_at_utc' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(\DateTimeInterface::ATOM),
            'source_artifacts' => [
                'manifest_file' => $manifestFile,
                'status_file' => $statusFile,
                'status_summary_file' => $statusSummaryFile,
                'final_seal_validation_file' => $finalSealValidationFile,
            ] + $sourceHashes,
            'checks' => $checks,
            'errors' => $errors,
        ];

        $encodedReceipt = (string) json_encode($receipt, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (null !== $outputFile) {
            $this->writeArtifact($outputFile, $encodedReceipt);
        }

        if (null !== $textFile) {
            $this->writeArtifact($textFile, $this->buildReceiptText($receipt));
        }

        if ((bool) $input->getOption('json')) {
            $output->writeln($encodedReceipt);

            return $ready ? Command::SUCCESS : Command::FAILURE;
        }

        $io->title('Administering 3RC receipt');
        $io->definitionList(
            ['status' => $receipt['status']],
            ['receipt ready' => $ready ? 'yes' : 'no'],
            ['fingerprint' => $receipt['receipt_fingerprint']],
            ['output file' => $outputFile ?? '(not written)'],
            ['text file' => $textFile ?? '(not written)'],
        );

        if ([] !== $errors) {
            $io->error($errors);

            return Command::FAILURE;
        }

        $io->success('Administering 3RC receipt is ready.');

        return Command::SUCCESS;
    }

    /** @param array<string, mixed> $receipt */
    private function buildReceiptText(array $receipt): string
    {
        $sourceArtifacts = is_array($receipt['source_artifacts'] ?? null) ? $receipt['source_artifacts'] : [];
        $errors = is_array($receipt['errors'] ?? null) ? $receipt['errors'] : [];
        $lines = [
            'Administering 3RC Receipt',
            '==========================',
            '',
            sprintf('Status: %s', (string) ($receipt['status'] ?? 'unknown')),
            sprintf('Receipt ready: %s', true === ($receipt['receipt_ready'] ?? false) ? 'yes' : 'no'),
            sprintf('Component: %s', (string) ($receipt['component'] ?? 'Administering')),
            sprintf('Package: %s', (string) ($receipt['package'] ?? 'administering/admin')),
            sprintf('Namespace: %s', (string) ($receipt['namespace'] ?? 'App\\Administering')),
            sprintf('RC stage: %s', (string) ($receipt['rc_stage'] ?? '3RC-candidate')),
            sprintf('Sealed status: %s', (string) ($receipt['sealed_status'] ?? 'unknown')),
            sprintf('Final seal validation status: %s', (string) ($receipt['final_seal_validation_status'] ?? 'unknown')),
            sprintf('Receipt fingerprint: %s', (string) ($receipt['receipt_fingerprint'] ?? 'missing')),
            sprintf('Reported at UTC: %s', (string) ($receipt['reported_at_utc'] ?? 'unknown')),
            '',
            'Source artifact hashes:',
        ];

        foreach ($sourceArtifacts as $name => $value) {
            if (str_ends_with((string) $name, '_sha256')) {
                $lines[] = sprintf('- %s: %s', (string) $name, is_scalar($value) && '' !== (string) $value ? (string) $value : '(missing)');
            }
        }

        $lines[] = '';
        $lines[] = 'Errors:';
        if ([] === $errors) {
            $lines[] = '- none';
        } else {
            foreach ($errors as $error) {
                $lines[] = sprintf('- %s', is_scalar($error) ? (string) $error : json_encode($error));
            }
        }

        return implode("\n", $lines)."\n";
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

        file_put_contents($file, $contents);
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
}
