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
 * Validates the already-written Administering 3RC final seal artifact.
 *
 * The final-seal command proves the upstream owner-review artifacts while it is
 * producing the final seal. This validator is the stable after-the-fact check:
 * it re-reads the seal, compares all recorded SHA-256 hashes to the current
 * upstream files, verifies the manifest contract, and emits a separate
 * validation artifact for owner/watchdog intake.
 */
#[AsCommand(
    name: 'administering:rc:final-seal:validate',
    description: 'Validates the captured Administering 3RC final-seal artifact against current proof files.',
)]
final class AdministrationRcFinalSealValidateCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('final-seal-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-final-seal.json.', 'delivery/rc/runtime-proof-results/administering-rc-final-seal.json')
            ->addOption('proof-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-proof.json.', 'delivery/rc/runtime-proof-results/administering-rc-proof.json')
            ->addOption('index-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-proof-index.json.', 'delivery/rc/runtime-proof-results/administering-rc-proof-index.json')
            ->addOption('validation-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-proof-validation.json.', 'delivery/rc/runtime-proof-results/administering-rc-proof-validation.json')
            ->addOption('owner-review-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-owner-review.json.', 'delivery/rc/runtime-proof-results/administering-rc-owner-review.json')
            ->addOption('manifest-file', null, InputOption::VALUE_REQUIRED, 'Path to delivery/rc/manifest.yaml.', 'delivery/rc/manifest.yaml')
            ->addOption('output-file', null, InputOption::VALUE_REQUIRED, 'Optional path where the final-seal validation JSON should be written.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit a machine-readable final-seal validation report.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $finalSealFile = $this->pathOption($input->getOption('final-seal-file'));
        $proofFile = $this->pathOption($input->getOption('proof-file'));
        $indexFile = $this->pathOption($input->getOption('index-file'));
        $validationFile = $this->pathOption($input->getOption('validation-file'));
        $ownerReviewFile = $this->pathOption($input->getOption('owner-review-file'));
        $manifestFile = $this->pathOption($input->getOption('manifest-file'));
        $outputFile = $this->optionalPathOption($input->getOption('output-file'));

        $checks = [];
        $errors = [];

        $manifest = $this->readYaml($manifestFile, $checks, $errors, 'manifest');
        $seal = $this->readJson($finalSealFile, $checks, $errors, 'final_seal');
        $proof = $this->readJson($proofFile, $checks, $errors, 'proof');
        $index = $this->readJson($indexFile, $checks, $errors, 'index');
        $validation = $this->readJson($validationFile, $checks, $errors, 'validation');
        $ownerReview = $this->readJson($ownerReviewFile, $checks, $errors, 'owner_review');

        if (is_array($manifest)) {
            $this->validateManifest($manifest, $checks, $errors);
        }

        if (is_array($seal)) {
            $this->validateSeal($seal, $proofFile, $indexFile, $validationFile, $ownerReviewFile, $manifestFile, $checks, $errors);
        }

        if (is_array($proof)) {
            $this->addCheck($checks, $errors, 'proof_status_ready', 'ready' === ($proof['status'] ?? null), 'proof status=ready');
            $this->addCheck($checks, $errors, 'proof_ready_true', true === ($proof['ready'] ?? null), 'proof ready=true');
        }

        if (is_array($index)) {
            $this->addCheck($checks, $errors, 'index_status_captured', 'captured' === ($index['status'] ?? null), 'index status=captured');
            $this->addCheck($checks, $errors, 'index_manifest_hash_current', $this->hashMatches($manifestFile, $index['manifest_sha256'] ?? null), 'index manifest SHA-256 matches');
            $this->addCheck($checks, $errors, 'index_proof_hash_current', $this->hashMatches($proofFile, $index['proof_sha256'] ?? null), 'index proof SHA-256 matches');
        }

        if (is_array($validation)) {
            $this->addCheck($checks, $errors, 'proof_validation_status_valid', 'valid' === ($validation['status'] ?? null), 'proof validation status=valid');
            $this->addCheck($checks, $errors, 'proof_validation_valid_true', true === ($validation['valid'] ?? null), 'proof validation valid=true');
        }

        if (is_array($ownerReview)) {
            $this->addCheck($checks, $errors, 'owner_review_status_ready', 'ready_for_owner_review' === ($ownerReview['status'] ?? null), 'owner-review status=ready_for_owner_review');
            $this->addCheck($checks, $errors, 'owner_review_ready_true', true === ($ownerReview['ready_for_owner_review'] ?? null), 'owner-review ready=true');
        }

        if (is_array($seal) && is_array($proof) && is_array($index)) {
            $this->validateSealProofIndexPair($seal, $proof, $index, $checks, $errors);
        }

        $valid = [] === $errors;
        $report = [
            'schema_version' => '1.0',
            'component' => 'Administering',
            'package' => 'administering/admin',
            'namespace' => 'App\\Administering',
            'rc_stage' => '3RC-candidate',
            'status' => $valid ? 'final_seal_valid' : 'blocked',
            'final_seal_valid' => $valid,
            'validated_at_utc' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(\DateTimeInterface::ATOM),
            'artifacts' => [
                'manifest_file' => $manifestFile,
                'proof_file' => $proofFile,
                'index_file' => $indexFile,
                'validation_file' => $validationFile,
                'owner_review_file' => $ownerReviewFile,
                'final_seal_file' => $finalSealFile,
                'manifest_sha256' => $this->hashOrNull($manifestFile),
                'proof_sha256' => $this->hashOrNull($proofFile),
                'index_sha256' => $this->hashOrNull($indexFile),
                'validation_sha256' => $this->hashOrNull($validationFile),
                'owner_review_sha256' => $this->hashOrNull($ownerReviewFile),
                'final_seal_sha256' => $this->hashOrNull($finalSealFile),
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

        $io->title('Administering 3RC final-seal validation');
        $io->definitionList(
            ['status' => $report['status']],
            ['component' => $report['component']],
            ['final seal' => $finalSealFile],
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

        $io->success('Administering 3RC final seal is valid and current.');

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
     * @param array<string, mixed>                                $manifest
     * @param list<array{name: string, ok: bool, detail: string}> $checks
     * @param list<string>                                        $errors
     */
    private function validateManifest(array $manifest, array &$checks, array &$errors): void
    {
        $this->addCheck($checks, $errors, 'manifest_component', 'Administering' === ($manifest['component'] ?? null), 'component=Administering');
        $this->addCheck($checks, $errors, 'manifest_package', 'administering/admin' === ($manifest['package'] ?? null), 'package=administering/admin');
        $this->addCheck($checks, $errors, 'manifest_namespace', 'App\\Administering' === ($manifest['namespace'] ?? null), 'namespace=App\\Administering');
        $this->addCheck($checks, $errors, 'manifest_rc_stage', '3RC-candidate' === ($manifest['rc_stage'] ?? null), 'rc_stage=3RC-candidate');
        $this->addCheck($checks, $errors, 'manifest_final_seal_validation_artifact', isset($manifest['artifacts']['final_seal_validation']), 'artifacts.final_seal_validation exists');
    }

    /**
     * @param array<string, mixed>                                $seal
     * @param list<array{name: string, ok: bool, detail: string}> $checks
     * @param list<string>                                        $errors
     */
    private function validateSeal(array $seal, string $proofFile, string $indexFile, string $validationFile, string $ownerReviewFile, string $manifestFile, array &$checks, array &$errors): void
    {
        $this->addCheck($checks, $errors, 'seal_status', 'sealed_3rc_candidate' === ($seal['status'] ?? null), 'status=sealed_3rc_candidate');
        $this->addCheck($checks, $errors, 'seal_candidate_true', true === ($seal['sealed_3rc_candidate'] ?? null), 'sealed_3rc_candidate=true');
        $this->addCheck($checks, $errors, 'seal_errors_empty', [] === ($seal['errors'] ?? null), 'errors=[]');
        $this->addCheck($checks, $errors, 'seal_checks_present', isset($seal['checks']) && is_array($seal['checks']) && [] !== $seal['checks'], 'checks are present');

        $artifacts = $seal['artifacts'] ?? null;
        $this->addCheck($checks, $errors, 'seal_artifact_hash_map_present', is_array($artifacts), 'final-seal artifacts map exists');

        if (!is_array($artifacts)) {
            return;
        }

        $this->addCheck($checks, $errors, 'seal_manifest_hash_current', $this->hashMatches($manifestFile, $artifacts['manifest_sha256'] ?? null), 'manifest SHA-256 matches current file');
        $this->addCheck($checks, $errors, 'seal_proof_hash_current', $this->hashMatches($proofFile, $artifacts['proof_sha256'] ?? null), 'proof SHA-256 matches current file');
        $this->addCheck($checks, $errors, 'seal_index_hash_current', $this->hashMatches($indexFile, $artifacts['index_sha256'] ?? null), 'index SHA-256 matches current file');
        $this->addCheck($checks, $errors, 'seal_validation_hash_current', $this->hashMatches($validationFile, $artifacts['validation_sha256'] ?? null), 'validation SHA-256 matches current file');
        $this->addCheck($checks, $errors, 'seal_owner_review_hash_current', $this->hashMatches($ownerReviewFile, $artifacts['owner_review_sha256'] ?? null), 'owner-review SHA-256 matches current file');
    }

    /**
     * @param array<string, mixed>                                $seal
     * @param array<string, mixed>                                $proof
     * @param array<string, mixed>                                $index
     * @param list<array{name: string, ok: bool, detail: string}> $checks
     * @param list<string>                                        $errors
     */
    private function validateSealProofIndexPair(array $seal, array $proof, array $index, array &$checks, array &$errors): void
    {
        $sealSummary = $seal['proof_summary'] ?? null;
        $this->addCheck($checks, $errors, 'seal_proof_summary_present', is_array($sealSummary), 'proof_summary exists');

        if (!is_array($sealSummary)) {
            return;
        }

        $this->addCheck(
            $checks,
            $errors,
            'seal_operation_type_consistent',
            isset($sealSummary['operation_type'], $proof['operation_type'], $index['operation_type'])
                && $sealSummary['operation_type'] === $proof['operation_type']
                && $proof['operation_type'] === $index['operation_type'],
            sprintf('seal=%s proof=%s index=%s', (string) ($sealSummary['operation_type'] ?? ''), (string) ($proof['operation_type'] ?? ''), (string) ($index['operation_type'] ?? '')),
        );

        $this->addCheck(
            $checks,
            $errors,
            'seal_target_prefix_consistent',
            isset($sealSummary['target_prefix'], $proof['target_prefix'], $index['target_prefix'])
                && $sealSummary['target_prefix'] === $proof['target_prefix']
                && $proof['target_prefix'] === $index['target_prefix'],
            sprintf('seal=%s proof=%s index=%s', (string) ($sealSummary['target_prefix'] ?? ''), (string) ($proof['target_prefix'] ?? ''), (string) ($index['target_prefix'] ?? '')),
        );
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
