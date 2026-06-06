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
 * Emits the final read-only 3RC seal from owner-review artifacts.
 *
 * This command is intentionally conservative: it does not rerun proof, does not
 * mutate ACLs, and does not write to Doctrine. It validates that the captured
 * owner-review verdict is still consistent with the proof/index/validation files
 * and writes a small final seal JSON for owner/watchdog intake.
 */
#[AsCommand(
    name: 'administering:rc:final-seal',
    description: 'Validates Administering 3RC owner-review artifacts and emits the final seal JSON.',
)]
final class AdministrationRcFinalSealCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('proof-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-proof.json.', 'delivery/rc/runtime-proof-results/administering-rc-proof.json')
            ->addOption('index-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-proof-index.json.', 'delivery/rc/runtime-proof-results/administering-rc-proof-index.json')
            ->addOption('validation-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-proof-validation.json.', 'delivery/rc/runtime-proof-results/administering-rc-proof-validation.json')
            ->addOption('owner-review-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-owner-review.json.', 'delivery/rc/runtime-proof-results/administering-rc-owner-review.json')
            ->addOption('manifest-file', null, InputOption::VALUE_REQUIRED, 'Path to delivery/rc/manifest.yaml.', 'delivery/rc/manifest.yaml')
            ->addOption('output-file', null, InputOption::VALUE_REQUIRED, 'Optional path where the final seal JSON should be written.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit a machine-readable final seal report.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $proofFile = $this->pathOption($input->getOption('proof-file'));
        $indexFile = $this->pathOption($input->getOption('index-file'));
        $validationFile = $this->pathOption($input->getOption('validation-file'));
        $ownerReviewFile = $this->pathOption($input->getOption('owner-review-file'));
        $manifestFile = $this->pathOption($input->getOption('manifest-file'));
        $outputFile = $this->optionalPathOption($input->getOption('output-file'));

        $checks = [];
        $errors = [];

        $manifest = $this->readYaml($manifestFile, $checks, $errors, 'manifest');
        $proof = $this->readJson($proofFile, $checks, $errors, 'proof');
        $index = $this->readJson($indexFile, $checks, $errors, 'index');
        $validation = $this->readJson($validationFile, $checks, $errors, 'validation');
        $ownerReview = $this->readJson($ownerReviewFile, $checks, $errors, 'owner_review');

        if (is_array($manifest)) {
            $this->validateManifest($manifest, $checks, $errors);
        }

        if (is_array($proof)) {
            $this->validateProof($proof, $checks, $errors);
        }

        if (is_array($index)) {
            $this->validateIndex($index, $proofFile, $manifestFile, $checks, $errors);
        }

        if (is_array($validation)) {
            $this->validateValidation($validation, $checks, $errors);
        }

        if (is_array($ownerReview)) {
            $this->validateOwnerReview($ownerReview, $proofFile, $indexFile, $validationFile, $manifestFile, $checks, $errors);
        }

        if (is_array($proof) && is_array($index) && is_array($ownerReview)) {
            $this->validateCrossArtifactConsistency($proof, $index, $ownerReview, $checks, $errors);
        }

        $sealed = [] === $errors;
        $report = [
            'schema_version' => '1.0',
            'component' => 'Administering',
            'package' => 'administering/admin',
            'namespace' => 'App\\Administering',
            'rc_stage' => '3RC-candidate',
            'status' => $sealed ? 'sealed_3rc_candidate' : 'blocked',
            'sealed_3rc_candidate' => $sealed,
            'sealed_at_utc' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(\DateTimeInterface::ATOM),
            'artifacts' => [
                'manifest_file' => $manifestFile,
                'proof_file' => $proofFile,
                'index_file' => $indexFile,
                'validation_file' => $validationFile,
                'owner_review_file' => $ownerReviewFile,
                'manifest_sha256' => $this->hashOrNull($manifestFile),
                'proof_sha256' => $this->hashOrNull($proofFile),
                'index_sha256' => $this->hashOrNull($indexFile),
                'validation_sha256' => $this->hashOrNull($validationFile),
                'owner_review_sha256' => $this->hashOrNull($ownerReviewFile),
            ],
            'proof_summary' => [
                'operation_type' => is_array($proof) ? (string) ($proof['operation_type'] ?? '') : '',
                'target_prefix' => is_array($proof) ? (string) ($proof['target_prefix'] ?? '') : '',
                'commands' => is_array($proof) && isset($proof['commands']) && is_array($proof['commands']) ? count($proof['commands']) : 0,
            ],
            'checks' => $checks,
            'errors' => $errors,
        ];

        if (null !== $outputFile) {
            $directory = dirname($outputFile);
            if (!is_dir($directory)) {
                mkdir($directory, 0775, true);
            }

            file_put_contents($outputFile, (string) json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        if ((bool) $input->getOption('json')) {
            $output->writeln((string) json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $sealed ? Command::SUCCESS : Command::FAILURE;
        }

        $io->title('Administering 3RC final seal');
        $io->definitionList(
            ['status' => $report['status']],
            ['component' => $report['component']],
            ['RC stage' => $report['rc_stage']],
            ['owner review' => $ownerReviewFile],
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

        $io->success('Administering is sealed as a 3RC candidate for owner review.');

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
        $this->addCheck($checks, $errors, 'manifest_final_seal_artifact', isset($manifest['artifacts']['final_seal']), 'artifacts.final_seal exists');
    }

    /**
     * @param array<string, mixed>                                $proof
     * @param list<array{name: string, ok: bool, detail: string}> $checks
     * @param list<string>                                        $errors
     */
    private function validateProof(array $proof, array &$checks, array &$errors): void
    {
        $this->addCheck($checks, $errors, 'proof_status_ready', 'ready' === ($proof['status'] ?? null), 'status=ready');
        $this->addCheck($checks, $errors, 'proof_ready_true', true === ($proof['ready'] ?? null), 'ready=true');
        $this->addCheck($checks, $errors, 'proof_errors_empty', [] === ($proof['errors'] ?? null), 'errors=[]');
        $this->addCheck($checks, $errors, 'proof_commands_present', isset($proof['commands']) && is_array($proof['commands']) && count($proof['commands']) >= 3, 'at least 3 proof commands');
    }

    /**
     * @param array<string, mixed>                                $index
     * @param list<array{name: string, ok: bool, detail: string}> $checks
     * @param list<string>                                        $errors
     */
    private function validateIndex(array $index, string $proofFile, string $manifestFile, array &$checks, array &$errors): void
    {
        $this->addCheck($checks, $errors, 'index_status_captured', 'captured' === ($index['status'] ?? null), 'status=captured');
        $this->addCheck($checks, $errors, 'index_manifest_hash', $this->hashMatches($manifestFile, $index['manifest_sha256'] ?? null), 'manifest SHA-256 matches');
        $this->addCheck($checks, $errors, 'index_proof_hash', $this->hashMatches($proofFile, $index['proof_sha256'] ?? null), 'proof SHA-256 matches');
    }

    /**
     * @param array<string, mixed>                                $validation
     * @param list<array{name: string, ok: bool, detail: string}> $checks
     * @param list<string>                                        $errors
     */
    private function validateValidation(array $validation, array &$checks, array &$errors): void
    {
        $this->addCheck($checks, $errors, 'validation_status_valid', 'valid' === ($validation['status'] ?? null), 'status=valid');
        $this->addCheck($checks, $errors, 'validation_valid_true', true === ($validation['valid'] ?? null), 'valid=true');
        $this->addCheck($checks, $errors, 'validation_errors_empty', [] === ($validation['errors'] ?? null), 'errors=[]');
    }

    /**
     * @param array<string, mixed>                                $ownerReview
     * @param list<array{name: string, ok: bool, detail: string}> $checks
     * @param list<string>                                        $errors
     */
    private function validateOwnerReview(array $ownerReview, string $proofFile, string $indexFile, string $validationFile, string $manifestFile, array &$checks, array &$errors): void
    {
        $this->addCheck($checks, $errors, 'owner_review_status_ready', 'ready_for_owner_review' === ($ownerReview['status'] ?? null), 'status=ready_for_owner_review');
        $this->addCheck($checks, $errors, 'owner_review_ready_true', true === ($ownerReview['ready_for_owner_review'] ?? null), 'ready_for_owner_review=true');
        $this->addCheck($checks, $errors, 'owner_review_errors_empty', [] === ($ownerReview['errors'] ?? null), 'errors=[]');
        $this->addCheck($checks, $errors, 'owner_review_checks_present', isset($ownerReview['checks']) && is_array($ownerReview['checks']) && [] !== $ownerReview['checks'], 'checks are present');

        $artifacts = $ownerReview['artifacts'] ?? null;
        $this->addCheck($checks, $errors, 'owner_review_artifact_hash_map_present', is_array($artifacts), 'owner-review artifacts map exists');

        if (!is_array($artifacts)) {
            return;
        }

        $this->addCheck($checks, $errors, 'owner_review_manifest_hash_current', $this->hashMatches($manifestFile, $artifacts['manifest_sha256'] ?? null), 'owner-review manifest SHA-256 matches current file');
        $this->addCheck($checks, $errors, 'owner_review_proof_hash_current', $this->hashMatches($proofFile, $artifacts['proof_sha256'] ?? null), 'owner-review proof SHA-256 matches current file');
        $this->addCheck($checks, $errors, 'owner_review_index_hash_current', $this->hashMatches($indexFile, $artifacts['index_sha256'] ?? null), 'owner-review index SHA-256 matches current file');
        $this->addCheck($checks, $errors, 'owner_review_validation_hash_current', $this->hashMatches($validationFile, $artifacts['validation_sha256'] ?? null), 'owner-review validation SHA-256 matches current file');
    }

    /**
     * @param array<string, mixed>                                $proof
     * @param array<string, mixed>                                $index
     * @param array<string, mixed>                                $ownerReview
     * @param list<array{name: string, ok: bool, detail: string}> $checks
     * @param list<string>                                        $errors
     */
    private function validateCrossArtifactConsistency(array $proof, array $index, array $ownerReview, array &$checks, array &$errors): void
    {
        $this->addCheck(
            $checks,
            $errors,
            'operation_type_consistent',
            isset($proof['operation_type'], $index['operation_type']) && $proof['operation_type'] === $index['operation_type'],
            sprintf('proof=%s index=%s', (string) ($proof['operation_type'] ?? ''), (string) ($index['operation_type'] ?? '')),
        );

        $this->addCheck(
            $checks,
            $errors,
            'target_prefix_consistent',
            isset($proof['target_prefix'], $index['target_prefix']) && $proof['target_prefix'] === $index['target_prefix'],
            sprintf('proof=%s index=%s', (string) ($proof['target_prefix'] ?? ''), (string) ($index['target_prefix'] ?? '')),
        );

        $artifacts = $ownerReview['artifacts'] ?? null;
        $this->addCheck($checks, $errors, 'owner_review_artifact_map_present', is_array($artifacts), 'owner-review artifact map exists');
    }

    private function hashMatches(string $file, mixed $expectedHash): bool
    {
        return is_string($expectedHash) && is_file($file) && hash_file('sha256', $file) === strtolower($expectedHash);
    }

    private function hashOrNull(string $file): ?string
    {
        return is_file($file) ? hash_file('sha256', $file) : null;
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
