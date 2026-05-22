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
 * Emits a compact read-only status summary for captured Administering 3RC artifacts.
 *
 * This command intentionally does not rerun proof and does not mutate Doctrine or
 * filesystem state except for the optional output JSON file. It is the operator
 * and watchdog-friendly inventory view after the full RC proof/final-seal chain
 * has already produced its artifacts.
 */
#[AsCommand(
    name: 'administering:rc:status',
    description: 'Summarizes captured Administering 3RC proof artifacts and final-seal validation status.',
)]
final class AdministrationRcStatusCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('manifest-file', null, InputOption::VALUE_REQUIRED, 'Path to delivery/rc/manifest.yaml.', 'delivery/rc/manifest.yaml')
            ->addOption('proof-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-proof.json.', 'delivery/rc/runtime-proof-results/administering-rc-proof.json')
            ->addOption('index-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-proof-index.json.', 'delivery/rc/runtime-proof-results/administering-rc-proof-index.json')
            ->addOption('validation-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-proof-validation.json.', 'delivery/rc/runtime-proof-results/administering-rc-proof-validation.json')
            ->addOption('owner-review-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-owner-review.json.', 'delivery/rc/runtime-proof-results/administering-rc-owner-review.json')
            ->addOption('final-seal-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-final-seal.json.', 'delivery/rc/runtime-proof-results/administering-rc-final-seal.json')
            ->addOption('final-seal-validation-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-final-seal-validation.json.', 'delivery/rc/runtime-proof-results/administering-rc-final-seal-validation.json')
            ->addOption('receipt-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-receipt.json.', 'delivery/rc/runtime-proof-results/administering-rc-receipt.json')
            ->addOption('receipt-text-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-receipt.txt.', 'delivery/rc/runtime-proof-results/administering-rc-receipt.txt')
            ->addOption('receipt-validation-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-receipt-validation.json.', 'delivery/rc/runtime-proof-results/administering-rc-receipt-validation.json')
            ->addOption('handoff-index-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-handoff-index.json.', 'delivery/rc/runtime-proof-results/administering-rc-handoff-index.json')
            ->addOption('handoff-index-text-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-handoff-index.txt.', 'delivery/rc/runtime-proof-results/administering-rc-handoff-index.txt')
            ->addOption('handoff-index-validation-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-handoff-index-validation.json.', 'delivery/rc/runtime-proof-results/administering-rc-handoff-index-validation.json')
            ->addOption('handoff-bundle-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-handoff-bundle.json.', 'delivery/rc/runtime-proof-results/administering-rc-handoff-bundle.json')
            ->addOption('handoff-bundle-text-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-handoff-bundle.txt.', 'delivery/rc/runtime-proof-results/administering-rc-handoff-bundle.txt')
            ->addOption('handoff-bundle-validation-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-handoff-bundle-validation.json.', 'delivery/rc/runtime-proof-results/administering-rc-handoff-bundle-validation.json')
            ->addOption('include-receipt-artifacts', null, InputOption::VALUE_NONE, 'Also require and summarize the final receipt and receipt-validation artifacts.')
            ->addOption('include-handoff-artifacts', null, InputOption::VALUE_NONE, 'Also require and summarize the terminal handoff index and handoff-index-validation artifacts.')
            ->addOption('include-handoff-bundle-artifacts', null, InputOption::VALUE_NONE, 'Also require and summarize the terminal handoff bundle and handoff-bundle-validation artifacts.')
            ->addOption('output-file', null, InputOption::VALUE_REQUIRED, 'Optional path where the RC status JSON should be written.')
            ->addOption('summary-file', null, InputOption::VALUE_REQUIRED, 'Optional path where a compact human-readable RC status summary should be written.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit a machine-readable RC status report.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $manifestFile = $this->pathOption($input->getOption('manifest-file'));
        $proofFile = $this->pathOption($input->getOption('proof-file'));
        $indexFile = $this->pathOption($input->getOption('index-file'));
        $validationFile = $this->pathOption($input->getOption('validation-file'));
        $ownerReviewFile = $this->pathOption($input->getOption('owner-review-file'));
        $finalSealFile = $this->pathOption($input->getOption('final-seal-file'));
        $finalSealValidationFile = $this->pathOption($input->getOption('final-seal-validation-file'));
        $receiptFile = $this->pathOption($input->getOption('receipt-file'));
        $receiptTextFile = $this->pathOption($input->getOption('receipt-text-file'));
        $receiptValidationFile = $this->pathOption($input->getOption('receipt-validation-file'));
        $handoffIndexFile = $this->pathOption($input->getOption('handoff-index-file'));
        $handoffIndexTextFile = $this->pathOption($input->getOption('handoff-index-text-file'));
        $handoffIndexValidationFile = $this->pathOption($input->getOption('handoff-index-validation-file'));
        $handoffBundleFile = $this->pathOption($input->getOption('handoff-bundle-file'));
        $handoffBundleTextFile = $this->pathOption($input->getOption('handoff-bundle-text-file'));
        $handoffBundleValidationFile = $this->pathOption($input->getOption('handoff-bundle-validation-file'));
        $includeReceiptArtifacts = (bool) $input->getOption('include-receipt-artifacts');
        $includeHandoffArtifacts = (bool) $input->getOption('include-handoff-artifacts');
        $includeHandoffBundleArtifacts = (bool) $input->getOption('include-handoff-bundle-artifacts');
        if ($includeHandoffBundleArtifacts) {
            $includeReceiptArtifacts = true;
            $includeHandoffArtifacts = true;
        }
        $outputFile = $this->optionalPathOption($input->getOption('output-file'));
        $summaryFile = $this->optionalPathOption($input->getOption('summary-file'));

        $checks = [];
        $errors = [];

        $manifest = $this->readYaml($manifestFile, $checks, $errors, 'manifest');
        $proof = $this->readJson($proofFile, $checks, $errors, 'proof');
        $index = $this->readJson($indexFile, $checks, $errors, 'index');
        $validation = $this->readJson($validationFile, $checks, $errors, 'validation');
        $ownerReview = $this->readJson($ownerReviewFile, $checks, $errors, 'owner_review');
        $finalSeal = $this->readJson($finalSealFile, $checks, $errors, 'final_seal');
        $finalSealValidation = $this->readJson($finalSealValidationFile, $checks, $errors, 'final_seal_validation');
        $receipt = $includeReceiptArtifacts ? $this->readJson($receiptFile, $checks, $errors, 'receipt') : null;
        $receiptValidation = $includeReceiptArtifacts ? $this->readJson($receiptValidationFile, $checks, $errors, 'receipt_validation') : null;
        $handoffIndex = $includeHandoffArtifacts ? $this->readJson($handoffIndexFile, $checks, $errors, 'handoff_index') : null;
        $handoffIndexValidation = $includeHandoffArtifacts ? $this->readJson($handoffIndexValidationFile, $checks, $errors, 'handoff_index_validation') : null;
        $handoffBundle = $includeHandoffBundleArtifacts ? $this->readJson($handoffBundleFile, $checks, $errors, 'handoff_bundle') : null;
        $handoffBundleValidation = $includeHandoffBundleArtifacts ? $this->readJson($handoffBundleValidationFile, $checks, $errors, 'handoff_bundle_validation') : null;
        $receiptTextExists = !$includeReceiptArtifacts || is_file($receiptTextFile);
        if ($includeReceiptArtifacts) {
            $this->addCheck($checks, $errors, 'receipt_text_file_exists', $receiptTextExists, $receiptTextFile);
        }

        $handoffIndexTextExists = !$includeHandoffArtifacts || is_file($handoffIndexTextFile);
        if ($includeHandoffArtifacts) {
            $this->addCheck($checks, $errors, 'handoff_index_text_file_exists', $handoffIndexTextExists, $handoffIndexTextFile);
        }

        $handoffBundleTextExists = !$includeHandoffBundleArtifacts || is_file($handoffBundleTextFile);
        if ($includeHandoffBundleArtifacts) {
            $this->addCheck($checks, $errors, 'handoff_bundle_text_file_exists', $handoffBundleTextExists, $handoffBundleTextFile);
        }

        if (is_array($manifest)) {
            $this->addCheck($checks, $errors, 'manifest_component', 'Administering' === ($manifest['component'] ?? null), 'component=Administering');
            $this->addCheck($checks, $errors, 'manifest_package', 'administering/admin' === ($manifest['package'] ?? null), 'package=administering/admin');
            $this->addCheck($checks, $errors, 'manifest_namespace', 'App\\Administering' === ($manifest['namespace'] ?? null), 'namespace=App\\Administering');
            $this->addCheck($checks, $errors, 'manifest_rc_stage', '3RC-candidate' === ($manifest['rc_stage'] ?? null), 'rc_stage=3RC-candidate');
            $this->addCheck($checks, $errors, 'manifest_status_artifact', isset($manifest['artifacts']['rc_status']), 'artifacts.rc_status exists');
            if ($includeReceiptArtifacts) {
                $this->addCheck($checks, $errors, 'manifest_receipt_artifact', isset($manifest['artifacts']['rc_receipt']), 'artifacts.rc_receipt exists');
                $this->addCheck($checks, $errors, 'manifest_receipt_validation_artifact', isset($manifest['artifacts']['rc_receipt_validation']), 'artifacts.rc_receipt_validation exists');
            }

            if ($includeHandoffArtifacts) {
                $this->addCheck($checks, $errors, 'manifest_handoff_index_artifact', isset($manifest['artifacts']['rc_handoff_index']), 'artifacts.rc_handoff_index exists');
                $this->addCheck($checks, $errors, 'manifest_handoff_index_validation_artifact', isset($manifest['artifacts']['rc_handoff_index_validation']), 'artifacts.rc_handoff_index_validation exists');
            }

            if ($includeHandoffBundleArtifacts) {
                $this->addCheck($checks, $errors, 'manifest_handoff_bundle_artifact', isset($manifest['artifacts']['rc_handoff_bundle']), 'artifacts.rc_handoff_bundle exists');
                $this->addCheck($checks, $errors, 'manifest_handoff_bundle_validation_artifact', isset($manifest['artifacts']['rc_handoff_bundle_validation']), 'artifacts.rc_handoff_bundle_validation exists');
            }
        }

        $this->assertJsonStatus($proof, 'proof', 'ready', 'ready', $checks, $errors);
        $this->assertJsonStatus($index, 'index', 'captured', null, $checks, $errors);
        $this->assertJsonStatus($validation, 'validation', 'valid', 'valid', $checks, $errors);
        $this->assertJsonStatus($ownerReview, 'owner_review', 'ready_for_owner_review', 'ready_for_owner_review', $checks, $errors);
        $this->assertJsonStatus($finalSeal, 'final_seal', 'sealed_3rc_candidate', 'sealed_3rc_candidate', $checks, $errors);
        $this->assertJsonStatus($finalSealValidation, 'final_seal_validation', 'final_seal_valid', 'final_seal_valid', $checks, $errors);
        if ($includeReceiptArtifacts) {
            $this->assertJsonStatus($receipt, 'receipt', '3rc_receipt_ready', 'receipt_ready', $checks, $errors);
            $this->assertJsonStatus($receiptValidation, 'receipt_validation', '3rc_receipt_valid', 'receipt_valid', $checks, $errors);
        }

        if ($includeHandoffArtifacts) {
            $this->assertJsonStatus($handoffIndex, 'handoff_index', '3rc_handoff_index_ready', 'handoff_index_ready', $checks, $errors);
            $this->assertJsonStatus($handoffIndexValidation, 'handoff_index_validation', '3rc_handoff_index_valid', 'handoff_index_valid', $checks, $errors);
        }

        if ($includeHandoffBundleArtifacts) {
            $this->assertJsonStatus($handoffBundle, 'handoff_bundle', '3rc_handoff_bundle_ready', 'handoff_bundle_ready', $checks, $errors);
            $this->assertJsonStatus($handoffBundleValidation, 'handoff_bundle_validation', '3rc_handoff_bundle_valid', 'handoff_bundle_valid', $checks, $errors);
        }

        if (is_array($finalSealValidation)) {
            $artifacts = $finalSealValidation['artifacts'] ?? null;
            $this->addCheck($checks, $errors, 'final_seal_validation_artifacts_present', is_array($artifacts), 'final-seal validation artifacts map exists');
            if (is_array($artifacts)) {
                $this->addCheck($checks, $errors, 'final_seal_validation_manifest_hash_current', $this->hashMatches($manifestFile, $artifacts['manifest_sha256'] ?? null), 'manifest hash matches current file');
                $this->addCheck($checks, $errors, 'final_seal_validation_proof_hash_current', $this->hashMatches($proofFile, $artifacts['proof_sha256'] ?? null), 'proof hash matches current file');
                $this->addCheck($checks, $errors, 'final_seal_validation_index_hash_current', $this->hashMatches($indexFile, $artifacts['index_sha256'] ?? null), 'index hash matches current file');
                $this->addCheck($checks, $errors, 'final_seal_validation_validation_hash_current', $this->hashMatches($validationFile, $artifacts['validation_sha256'] ?? null), 'validation hash matches current file');
                $this->addCheck($checks, $errors, 'final_seal_validation_owner_review_hash_current', $this->hashMatches($ownerReviewFile, $artifacts['owner_review_sha256'] ?? null), 'owner-review hash matches current file');
                $this->addCheck($checks, $errors, 'final_seal_validation_final_seal_hash_current', $this->hashMatches($finalSealFile, $artifacts['final_seal_sha256'] ?? null), 'final-seal hash matches current file');
            }
        }

        if ($includeHandoffArtifacts && is_array($handoffIndexValidation)) {
            $artifacts = $handoffIndexValidation['artifacts'] ?? null;
            $this->addCheck($checks, $errors, 'handoff_index_validation_artifacts_present', is_array($artifacts), 'handoff-index validation artifacts map exists');
            if (is_array($artifacts)) {
                $this->addCheck($checks, $errors, 'handoff_index_validation_manifest_hash_current', $this->hashMatches($manifestFile, $artifacts['manifest_sha256'] ?? null), 'manifest hash matches current file');
                $this->addCheck($checks, $errors, 'handoff_index_validation_final_status_hash_current', $this->hashMatches($artifacts['final_status_file'] ?? $handoffIndexFile, $artifacts['final_status_sha256'] ?? null), 'final-status hash matches current file when file path is recorded');
                $this->addCheck($checks, $errors, 'handoff_index_validation_handoff_index_hash_current', $this->hashMatches($handoffIndexFile, $artifacts['handoff_index_sha256'] ?? null), 'handoff-index hash matches current file');
            }
        }

        if ($includeHandoffBundleArtifacts && is_array($handoffBundleValidation)) {
            $artifacts = $handoffBundleValidation['artifacts'] ?? null;
            $this->addCheck($checks, $errors, 'handoff_bundle_validation_artifacts_present', is_array($artifacts), 'handoff-bundle validation artifacts map exists');
            if (is_array($artifacts)) {
                $this->addCheck($checks, $errors, 'handoff_bundle_validation_manifest_hash_current', $this->hashMatches($manifestFile, $artifacts['manifest_sha256'] ?? null), 'manifest hash matches current file');
                $this->addCheck($checks, $errors, 'handoff_bundle_validation_handoff_bundle_hash_current', $this->hashMatches($handoffBundleFile, $artifacts['handoff_bundle_sha256'] ?? null), 'handoff-bundle hash matches current file');
                $this->addCheck($checks, $errors, 'handoff_bundle_validation_handoff_bundle_text_hash_current', $this->hashMatches($handoffBundleTextFile, $artifacts['handoff_bundle_text_sha256'] ?? null), 'handoff-bundle text hash matches current file');
            }
        }

        $ready = [] === $errors;
        $report = [
            'schema_version' => '1.0',
            'component' => 'Administering',
            'package' => 'administering/admin',
            'namespace' => 'App\\Administering',
            'rc_stage' => '3RC-candidate',
            'status' => $ready ? 'sealed_3rc_validated' : 'blocked',
            'sealed_3rc_validated' => $ready,
            'reported_at_utc' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(\DateTimeInterface::ATOM),
            'include_receipt_artifacts' => $includeReceiptArtifacts,
            'include_handoff_artifacts' => $includeHandoffArtifacts,
            'include_handoff_bundle_artifacts' => $includeHandoffBundleArtifacts,
            'artifact_status' => [
                'proof' => $this->jsonStatus($proof),
                'index' => $this->jsonStatus($index),
                'validation' => $this->jsonStatus($validation),
                'owner_review' => $this->jsonStatus($ownerReview),
                'final_seal' => $this->jsonStatus($finalSeal),
                'final_seal_validation' => $this->jsonStatus($finalSealValidation),
                'receipt' => $includeReceiptArtifacts ? $this->jsonStatus($receipt) : null,
                'receipt_validation' => $includeReceiptArtifacts ? $this->jsonStatus($receiptValidation) : null,
                'handoff_index' => $includeHandoffArtifacts ? $this->jsonStatus($handoffIndex) : null,
                'handoff_index_validation' => $includeHandoffArtifacts ? $this->jsonStatus($handoffIndexValidation) : null,
                'handoff_bundle' => $includeHandoffBundleArtifacts ? $this->jsonStatus($handoffBundle) : null,
                'handoff_bundle_validation' => $includeHandoffBundleArtifacts ? $this->jsonStatus($handoffBundleValidation) : null,
            ],
            'artifacts' => [
                'manifest_file' => $manifestFile,
                'proof_file' => $proofFile,
                'index_file' => $indexFile,
                'validation_file' => $validationFile,
                'owner_review_file' => $ownerReviewFile,
                'final_seal_file' => $finalSealFile,
                'final_seal_validation_file' => $finalSealValidationFile,
                'receipt_file' => $includeReceiptArtifacts ? $receiptFile : null,
                'receipt_text_file' => $includeReceiptArtifacts ? $receiptTextFile : null,
                'receipt_validation_file' => $includeReceiptArtifacts ? $receiptValidationFile : null,
                'handoff_index_file' => $includeHandoffArtifacts ? $handoffIndexFile : null,
                'handoff_index_text_file' => $includeHandoffArtifacts ? $handoffIndexTextFile : null,
                'handoff_index_validation_file' => $includeHandoffArtifacts ? $handoffIndexValidationFile : null,
                'handoff_bundle_file' => $includeHandoffBundleArtifacts ? $handoffBundleFile : null,
                'handoff_bundle_text_file' => $includeHandoffBundleArtifacts ? $handoffBundleTextFile : null,
                'handoff_bundle_validation_file' => $includeHandoffBundleArtifacts ? $handoffBundleValidationFile : null,
                'manifest_sha256' => $this->hashOrNull($manifestFile),
                'proof_sha256' => $this->hashOrNull($proofFile),
                'index_sha256' => $this->hashOrNull($indexFile),
                'validation_sha256' => $this->hashOrNull($validationFile),
                'owner_review_sha256' => $this->hashOrNull($ownerReviewFile),
                'final_seal_sha256' => $this->hashOrNull($finalSealFile),
                'final_seal_validation_sha256' => $this->hashOrNull($finalSealValidationFile),
                'receipt_sha256' => $includeReceiptArtifacts ? $this->hashOrNull($receiptFile) : null,
                'receipt_text_sha256' => $includeReceiptArtifacts ? $this->hashOrNull($receiptTextFile) : null,
                'receipt_validation_sha256' => $includeReceiptArtifacts ? $this->hashOrNull($receiptValidationFile) : null,
                'handoff_index_sha256' => $includeHandoffArtifacts ? $this->hashOrNull($handoffIndexFile) : null,
                'handoff_index_text_sha256' => $includeHandoffArtifacts ? $this->hashOrNull($handoffIndexTextFile) : null,
                'handoff_index_validation_sha256' => $includeHandoffArtifacts ? $this->hashOrNull($handoffIndexValidationFile) : null,
                'handoff_bundle_sha256' => $includeHandoffBundleArtifacts ? $this->hashOrNull($handoffBundleFile) : null,
                'handoff_bundle_text_sha256' => $includeHandoffBundleArtifacts ? $this->hashOrNull($handoffBundleTextFile) : null,
                'handoff_bundle_validation_sha256' => $includeHandoffBundleArtifacts ? $this->hashOrNull($handoffBundleValidationFile) : null,
            ],
            'checks' => $checks,
            'errors' => $errors,
        ];

        $encodedReport = (string) json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (null !== $outputFile) {
            $this->writeJsonArtifact($outputFile, $encodedReport);
        }

        if (null !== $summaryFile) {
            $this->writeTextArtifact($summaryFile, $this->buildSummary($report));
        }

        if ((bool) $input->getOption('json')) {
            $output->writeln($encodedReport);

            return $ready ? Command::SUCCESS : Command::FAILURE;
        }

        $io->title('Administering 3RC status');
        $io->definitionList(
            ['status' => $report['status']],
            ['component' => $report['component']],
            ['RC stage' => $report['rc_stage']],
            ['output file' => $outputFile ?? '(not written)'],
            ['summary file' => $summaryFile ?? '(not written)'],
        );

        $io->table(['Artifact', 'Status'], array_map(
            static fn (string $name, mixed $status): array => [$name, is_scalar($status) ? (string) $status : '(missing)'],
            array_keys($report['artifact_status']),
            array_values($report['artifact_status']),
        ));

        if ([] !== $errors) {
            $io->error($errors);

            return Command::FAILURE;
        }

        $io->success('Administering 3RC artifacts are captured, sealed, validated, and terminal handoff-ready.');

        return Command::SUCCESS;
    }

    /** @param array<string, mixed> $report */
    private function buildSummary(array $report): string
    {
        $lines = [
            'Administering 3RC Status',
            '=========================',
            '',
            sprintf('Status: %s', (string) ($report['status'] ?? 'unknown')),
            sprintf('Sealed 3RC validated: %s', true === ($report['sealed_3rc_validated'] ?? false) ? 'yes' : 'no'),
            sprintf('Component: %s', (string) ($report['component'] ?? 'Administering')),
            sprintf('Package: %s', (string) ($report['package'] ?? 'administering/admin')),
            sprintf('Namespace: %s', (string) ($report['namespace'] ?? 'App\\Administering')),
            sprintf('RC stage: %s', (string) ($report['rc_stage'] ?? '3RC-candidate')),
            sprintf('Reported at UTC: %s', (string) ($report['reported_at_utc'] ?? 'unknown')),
            '',
            'Artifact statuses:',
        ];

        $artifactStatus = $report['artifact_status'] ?? [];
        if (is_array($artifactStatus)) {
            foreach ($artifactStatus as $name => $status) {
                $lines[] = sprintf('- %s: %s', (string) $name, is_scalar($status) ? (string) $status : '(missing)');
            }
        }

        $lines[] = '';
        $lines[] = 'Artifact hashes:';
        $artifacts = $report['artifacts'] ?? [];
        if (is_array($artifacts)) {
            foreach ($artifacts as $name => $value) {
                if (str_ends_with((string) $name, '_sha256')) {
                    $lines[] = sprintf('- %s: %s', (string) $name, is_scalar($value) && '' !== (string) $value ? (string) $value : '(missing)');
                }
            }
        }

        $errors = $report['errors'] ?? [];
        $lines[] = '';
        $lines[] = 'Errors:';
        if ([] === $errors) {
            $lines[] = '- none';
        } elseif (is_array($errors)) {
            foreach ($errors as $error) {
                $lines[] = sprintf('- %s', is_scalar($error) ? (string) $error : json_encode($error));
            }
        }

        return implode("\n", $lines)."\n";
    }

    private function writeTextArtifact(string $file, string $contents): void
    {
        $directory = dirname($file);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        file_put_contents($file, $contents);
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
     * @param array<string, mixed>|null                           $decoded
     * @param list<array{name: string, ok: bool, detail: string}> $checks
     * @param list<string>                                        $errors
     */
    private function assertJsonStatus(?array $decoded, string $label, string $expectedStatus, ?string $expectedBoolKey, array &$checks, array &$errors): void
    {
        if (!is_array($decoded)) {
            $this->addCheck($checks, $errors, $label.'_available_for_status_check', false, 'artifact missing or not parseable');

            return;
        }

        $this->addCheck($checks, $errors, $label.'_status', $expectedStatus === ($decoded['status'] ?? null), sprintf('status=%s', $expectedStatus));
        if (null !== $expectedBoolKey) {
            $this->addCheck($checks, $errors, $label.'_'.$expectedBoolKey.'_true', true === ($decoded[$expectedBoolKey] ?? null), sprintf('%s=true', $expectedBoolKey));
        }

        if (array_key_exists('errors', $decoded)) {
            $this->addCheck($checks, $errors, $label.'_errors_empty', [] === ($decoded['errors'] ?? null), 'errors=[]');
        }
    }

    /** @param array<string, mixed>|null $decoded */
    private function jsonStatus(?array $decoded): ?string
    {
        return is_array($decoded) && is_string($decoded['status'] ?? null) ? $decoded['status'] : null;
    }

    private function hashMatches(string $file, mixed $expectedHash): bool
    {
        return is_string($expectedHash) && is_file($file) && hash_file('sha256', $file) === strtolower($expectedHash);
    }

    private function hashOrNull(string $file): ?string
    {
        return is_file($file) ? hash_file('sha256', $file) : null;
    }

    private function writeJsonArtifact(string $file, string $contents): void
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
