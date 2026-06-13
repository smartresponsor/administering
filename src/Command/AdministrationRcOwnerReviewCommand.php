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
 * Produces the final owner-facing 3RC review verdict from captured proof artifacts.
 *
 * This command is deliberately read-only. It does not rerun runtime proof, mutate
 * ACLs, or touch system storage. It validates the captured proof/index/validation
 * files and emits a stable owner-review JSON artifact for handoff tooling.
 */
#[AsCommand(
    name: 'administering:rc:owner-review',
    description: 'Validates captured Administering 3RC proof artifacts and emits the owner-review verdict.',
)]
final class AdministrationRcOwnerReviewCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('proof-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-proof.json.', 'delivery/rc/runtime-proof-results/administering-rc-proof.json')
            ->addOption('index-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-proof-index.json.', 'delivery/rc/runtime-proof-results/administering-rc-proof-index.json')
            ->addOption('validation-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-proof-validation.json.', 'delivery/rc/runtime-proof-results/administering-rc-proof-validation.json')
            ->addOption('manifest-file', null, InputOption::VALUE_REQUIRED, 'Path to delivery/rc/manifest.yaml.', 'delivery/rc/manifest.yaml')
            ->addOption('output-file', null, InputOption::VALUE_REQUIRED, 'Optional path where the owner-review JSON artifact should be written.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit a machine-readable owner-review report.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $proofFile = $this->pathOption($input->getOption('proof-file'));
        $indexFile = $this->pathOption($input->getOption('index-file'));
        $validationFile = $this->pathOption($input->getOption('validation-file'));
        $manifestFile = $this->pathOption($input->getOption('manifest-file'));
        $outputFile = $this->optionalPathOption($input->getOption('output-file'));

        $checks = [];
        $errors = [];

        $manifest = $this->readYaml($manifestFile, $checks, $errors, 'manifest');
        $proof = $this->readJson($proofFile, $checks, $errors, 'proof');
        $index = $this->readJson($indexFile, $checks, $errors, 'index');
        $validation = $this->readJson($validationFile, $checks, $errors, 'validation');

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

        if (is_array($proof) && is_array($index)) {
            $this->validateProofIndexPair($proof, $index, $checks, $errors);
        }

        $ready = [] === $errors;
        $report = [
            'schema_version' => '1.0',
            'component' => 'Administering',
            'rc_stage' => '3RC-candidate',
            'status' => $ready ? 'ready_for_owner_review' : 'blocked',
            'ready_for_owner_review' => $ready,
            'artifacts' => [
                'manifest_file' => $manifestFile,
                'proof_file' => $proofFile,
                'index_file' => $indexFile,
                'validation_file' => $validationFile,
                'manifest_sha256' => $this->hashOrNull($manifestFile),
                'proof_sha256' => $this->hashOrNull($proofFile),
                'index_sha256' => $this->hashOrNull($indexFile),
                'validation_sha256' => $this->hashOrNull($validationFile),
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

            return $ready ? Command::SUCCESS : Command::FAILURE;
        }

        $io->title('Administering 3RC owner review');
        $io->definitionList(
            ['status' => $report['status']],
            ['component' => $report['component']],
            ['RC stage' => $report['rc_stage']],
            ['manifest' => $manifestFile],
            ['proof' => $proofFile],
            ['index' => $indexFile],
            ['validation' => $validationFile],
        );

        $io->table(['Check', 'Result', 'Detail'], array_map(
            static fn (array $check): array => [$check['nameEntity'], $check['ok'] ? 'ok' : 'failed', $check['detail']],
            $checks,
        ));

        if ([] !== $errors) {
            $io->error($errors);

            return Command::FAILURE;
        }

        $io->success('Administering is ready for owner review as a 3RC candidate.');

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
        $this->addCheck($checks, $errors, 'manifest_owner_review_artifact', isset($manifest['artifacts']['owner_review']), 'artifacts.owner_review exists');
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
        $this->addCheck($checks, $errors, 'proof_commands_present', isset($proof['commands']) && is_array($proof['commands']) && count($proof['commands']) >= 3, 'at least 3 section commands');

        if (!isset($proof['commands']) || !is_array($proof['commands'])) {
            return;
        }

        foreach ($proof['commands'] as $offset => $command) {
            if (!is_array($command)) {
                $this->addCheck($checks, $errors, sprintf('proof_command_%d_shape', $offset), false, 'command report is not a map');
                continue;
            }

            $nameEntity = (string) ($command['command'] ?? 'unknown');
            $this->addCheck($checks, $errors, sprintf('proof_command_%d_exit', $offset), 0 === ($command['exit_code'] ?? null), $nameEntity);
            $this->addCheck($checks, $errors, sprintf('proof_command_%d_json', $offset), true === ($command['json_valid'] ?? null), $nameEntity);
        }
    }

    /**
     * @param array<string, mixed>                                $index
     * @param list<array{name: string, ok: bool, detail: string}> $checks
     * @param list<string>                                        $errors
     */
    private function validateIndex(array $index, string $proofFile, string $manifestFile, array &$checks, array &$errors): void
    {
        $this->addCheck($checks, $errors, 'index_status_captured', 'captured' === ($index['status'] ?? null), 'status=captured');
        $this->addCheck($checks, $errors, 'index_component', 'Administering' === ($index['component'] ?? null), 'component=Administering');
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
        $this->addCheck($checks, $errors, 'validation_checks_present', isset($validation['checks']) && is_array($validation['checks']) && [] !== $validation['checks'], 'checks are present');
    }

    /**
     * @param array<string, mixed>                                $proof
     * @param array<string, mixed>                                $index
     * @param list<array{name: string, ok: bool, detail: string}> $checks
     * @param list<string>                                        $errors
     */
    private function validateProofIndexPair(array $proof, array $index, array &$checks, array &$errors): void
    {
        $this->addCheck(
            $checks,
            $errors,
            'operation_type_matches_index',
            isset($proof['operation_type'], $index['operation_type']) && $proof['operation_type'] === $index['operation_type'],
            sprintf('proof=%s index=%s', (string) ($proof['operation_type'] ?? ''), (string) ($index['operation_type'] ?? '')),
        );

        $this->addCheck(
            $checks,
            $errors,
            'target_prefix_matches_index',
            isset($proof['target_prefix'], $index['target_prefix']) && $proof['target_prefix'] === $index['target_prefix'],
            sprintf('proof=%s index=%s', (string) ($proof['target_prefix'] ?? ''), (string) ($index['target_prefix'] ?? '')),
        );
    }

    private function hashMatches(string $file, mixed $expectedHash): bool
    {
        return is_string($expectedHash) && is_file($file) && hash_file('sha256', $file) === strtolower($expectedHash);
    }

    private function hashOrNull(string $file): ?string
    {
        if (!is_file($file)) {
            return null;
        }

        $hash = hash_file('sha256', $file);

        return false === $hash ? null : $hash;
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
    private function addCheck(array &$checks, array &$errors, string $nameEntity, bool $ok, string $detail): void
    {
        $checks[] = [
            'nameEntity' => $nameEntity,
            'ok' => $ok,
            'detail' => $detail,
        ];

        if (!$ok) {
            $errors[] = sprintf('%s failed: %s', $nameEntity, $detail);
        }
    }
}
