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
 * Validates captured Administering RC proof artifacts after owner/CI proof capture.
 *
 * The aggregate proof command proves runtime behavior. This validator proves that
 * the handoff files written to delivery/rc/runtime-proof-results are present,
 * parseable, internally consistent, and bound to the RC manifest by SHA-256.
 */
#[AsCommand(
    name: 'administering:rc:proof-artifact:validate',
    description: 'Validates captured Administering RC proof/index artifacts against the RC manifest contract.',
)]
final class AdministrationRcProofArtifactValidateCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('proof-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-proof.json.', 'delivery/rc/runtime-proof-results/administering-rc-proof.json')
            ->addOption('index-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-proof-index.json.', 'delivery/rc/runtime-proof-results/administering-rc-proof-index.json')
            ->addOption('manifest-file', null, InputOption::VALUE_REQUIRED, 'Path to delivery/rc/manifest.yaml.', 'delivery/rc/manifest.yaml')
            ->addOption('output-file', null, InputOption::VALUE_REQUIRED, 'Optional path where the validation JSON artifact should be written.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit a machine-readable validation report.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $proofFile = $this->pathOption($input->getOption('proof-file'));
        $indexFile = $this->pathOption($input->getOption('index-file'));
        $manifestFile = $this->pathOption($input->getOption('manifest-file'));
        $outputFile = $this->optionalPathOption($input->getOption('output-file'));

        $checks = [];
        $errors = [];

        $manifest = $this->readYaml($manifestFile, $checks, $errors, 'manifest');
        $proof = $this->readJson($proofFile, $checks, $errors, 'proof');
        $index = $this->readJson($indexFile, $checks, $errors, 'index');

        if (is_array($manifest)) {
            $this->validateManifest($manifest, $checks, $errors);
        }

        if (is_array($proof)) {
            $this->validateProof($proof, $checks, $errors);
        }

        if (is_array($index)) {
            $this->validateIndex($index, $proofFile, $indexFile, $manifestFile, $checks, $errors);
        }

        if (is_array($proof) && is_array($index)) {
            $this->validateProofIndexPair($proof, $index, $checks, $errors);
        }

        $valid = [] === $errors;
        $report = [
            'status' => $valid ? 'valid' : 'invalid',
            'valid' => $valid,
            'proof_file' => $proofFile,
            'index_file' => $indexFile,
            'manifest_file' => $manifestFile,
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

        $io->title('Administering RC proof artifact validation');
        $io->definitionList(
            ['status' => $report['status']],
            ['proof file' => $proofFile],
            ['index file' => $indexFile],
            ['manifest file' => $manifestFile],
        );

        $io->table(['Check', 'Result', 'Detail'], array_map(
            static fn (array $check): array => [$check['name'], $check['ok'] ? 'ok' : 'failed', $check['detail']],
            $checks,
        ));

        if ([] !== $errors) {
            $io->error($errors);

            return Command::FAILURE;
        }

        $io->success('RC proof artifacts are valid.');

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
        $this->addCheck($checks, $errors, 'manifest_namespace', 'App\\Administering' === ($manifest['namespace'] ?? null), 'namespace=App\\Administering');
        $this->addCheck($checks, $errors, 'manifest_rc_stage', '3RC-candidate' === ($manifest['rc_stage'] ?? null), 'rc_stage=3RC-candidate');
        $this->addCheck($checks, $errors, 'manifest_has_proof_commands', isset($manifest['proof_commands']) && is_array($manifest['proof_commands']), 'proof_commands map exists');
        $this->addCheck($checks, $errors, 'manifest_has_artifacts', isset($manifest['artifacts']) && is_array($manifest['artifacts']), 'artifacts map exists');
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

            $name = (string) ($command['command'] ?? 'unknown');
            $this->addCheck($checks, $errors, sprintf('proof_command_%d_exit', $offset), 0 === ($command['exit_code'] ?? null), $name);
            $this->addCheck($checks, $errors, sprintf('proof_command_%d_json', $offset), true === ($command['json_valid'] ?? null), $name);
        }
    }

    /**
     * @param array<string, mixed>                                $index
     * @param list<array{name: string, ok: bool, detail: string}> $checks
     * @param list<string>                                        $errors
     */
    private function validateIndex(array $index, string $proofFile, string $indexFile, string $manifestFile, array &$checks, array &$errors): void
    {
        unset($indexFile);

        $this->addCheck($checks, $errors, 'index_status_captured', 'captured' === ($index['status'] ?? null), 'status=captured');
        $this->addCheck($checks, $errors, 'index_component', 'Administering' === ($index['component'] ?? null), 'component=Administering');
        $this->addCheck($checks, $errors, 'index_manifest_hash', $this->hashMatches($manifestFile, $index['manifest_sha256'] ?? null), 'manifest SHA-256 matches');
        $this->addCheck($checks, $errors, 'index_proof_hash', $this->hashMatches($proofFile, $index['proof_sha256'] ?? null), 'proof SHA-256 matches');
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
