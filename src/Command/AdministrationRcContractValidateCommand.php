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
 * Validates the static 3RC proof contract before running the expensive runtime chain.
 *
 * The command does not execute proof commands and does not create runtime artifacts.
 * It only checks that composer.json, delivery/rc/manifest.yaml, and the Windows
 * helper agree on the canonical RC command sequence and terminal handoff files.
 */
#[AsCommand(
    name: 'administering:rc:contract:validate',
    description: 'Validates the static Administering 3RC composer/manifest/helper contract.',
)]
final class AdministrationRcContractValidateCommand extends Command
{
    /** @var list<string> */
    private const EXPECTED_CHAIN = [
        '@rc:contract:validate',
        '@rc:proof',
        '@rc:proof:index',
        '@rc:proof:validate',
        '@rc:owner-review',
        '@rc:final-seal',
        '@rc:final-seal:validate',
        '@rc:status',
        '@rc:receipt',
        '@rc:receipt:validate',
        '@rc:status:final',
        '@rc:status:final:validate',
        '@rc:handoff-index',
        '@rc:handoff-index:validate',
        '@rc:status:terminal',
        '@rc:status:terminal:validate',
        '@rc:handoff-bundle',
        '@rc:handoff-bundle:validate',
        '@rc:status:bundle',
        '@rc:acceptance',
    ];

    /** @var list<string> */
    private const REQUIRED_ALIASES = [
        'rc:contract:validate',
        'rc:proof',
        'rc:proof:index',
        'rc:proof:validate',
        'rc:owner-review',
        'rc:final-seal',
        'rc:final-seal:validate',
        'rc:status',
        'rc:receipt',
        'rc:receipt:validate',
        'rc:status:final',
        'rc:status:final:validate',
        'rc:handoff-index',
        'rc:handoff-index:validate',
        'rc:status:terminal',
        'rc:status:terminal:validate',
        'rc:handoff-bundle',
        'rc:handoff-bundle:validate',
        'rc:status:bundle',
        'rc:acceptance',
    ];

    /** @var array<string, string> */
    private const REQUIRED_ALIAS_COMMAND_MARKERS = [
        'rc:contract:validate' => 'administering:rc:contract:validate',
        'rc:proof' => 'administering:rc:proof',
        'rc:proof:index' => 'administering:rc:proof-index',
        'rc:proof:validate' => 'administering:rc:proof-artifact:validate',
        'rc:owner-review' => 'administering:rc:owner-review',
        'rc:final-seal' => 'administering:rc:final-seal',
        'rc:final-seal:validate' => 'administering:rc:final-seal:validate',
        'rc:status' => 'administering:rc:status',
        'rc:receipt' => 'administering:rc:receipt',
        'rc:receipt:validate' => 'administering:rc:receipt:validate',
        'rc:status:final' => 'administering:rc:status',
        'rc:status:final:validate' => 'administering:rc:final-status:validate',
        'rc:handoff-index' => 'administering:rc:handoff-index',
        'rc:handoff-index:validate' => 'administering:rc:handoff-index:validate',
        'rc:status:terminal' => 'administering:rc:status',
        'rc:status:terminal:validate' => 'administering:rc:terminal-status:validate',
        'rc:handoff-bundle' => 'administering:rc:handoff-bundle',
        'rc:handoff-bundle:validate' => 'administering:rc:handoff-bundle:validate',
        'rc:status:bundle' => 'administering:rc:status',
        'rc:acceptance' => 'administering:rc:acceptance',
    ];

    /** @var list<string> */
    private const REQUIRED_STATIC_CONTRACT_COVERAGE = [
        'composer quality:rc-3rc canonical order',
        'required rc aliases exist',
        'required artifact paths are mentioned by composer rc aliases',
        'composer rc aliases invoke the expected Symfony console command names',
        'manifest composer_scripts lists every required rc alias with the expected composer command',
        'manifest composer_aliases lists every required rc alias with the expected composer command',
        'required artifact paths are listed in manifest artifacts',
        'required artifact paths are listed in windows_helper.writes',
        'required artifact paths are mentioned by the Windows helper',
        'README contains terminal handoff markers',
        'static_contract_validation coverage list is self-checked',
    ];

    /** @var list<string> */
    private const REQUIRED_ARTIFACTS = [
        'delivery/rc/runtime-proof-results/administering-rc-proof.json',
        'delivery/rc/runtime-proof-results/administering-rc-proof-index.json',
        'delivery/rc/runtime-proof-results/administering-rc-proof-validation.json',
        'delivery/rc/runtime-proof-results/administering-rc-owner-review.json',
        'delivery/rc/runtime-proof-results/administering-rc-final-seal.json',
        'delivery/rc/runtime-proof-results/administering-rc-final-seal-validation.json',
        'delivery/rc/runtime-proof-results/administering-rc-status.json',
        'delivery/rc/runtime-proof-results/administering-rc-status-summary.txt',
        'delivery/rc/runtime-proof-results/administering-rc-receipt.json',
        'delivery/rc/runtime-proof-results/administering-rc-receipt.txt',
        'delivery/rc/runtime-proof-results/administering-rc-receipt-validation.json',
        'delivery/rc/runtime-proof-results/administering-rc-final-status.json',
        'delivery/rc/runtime-proof-results/administering-rc-final-status-summary.txt',
        'delivery/rc/runtime-proof-results/administering-rc-final-status-validation.json',
        'delivery/rc/runtime-proof-results/administering-rc-handoff-index.json',
        'delivery/rc/runtime-proof-results/administering-rc-handoff-index.txt',
        'delivery/rc/runtime-proof-results/administering-rc-handoff-index-validation.json',
        'delivery/rc/runtime-proof-results/administering-rc-terminal-status.json',
        'delivery/rc/runtime-proof-results/administering-rc-terminal-status-summary.txt',
        'delivery/rc/runtime-proof-results/administering-rc-terminal-status-validation.json',
        'delivery/rc/runtime-proof-results/administering-rc-handoff-bundle.json',
        'delivery/rc/runtime-proof-results/administering-rc-handoff-bundle.txt',
        'delivery/rc/runtime-proof-results/administering-rc-handoff-bundle-validation.json',
        'delivery/rc/runtime-proof-results/administering-rc-bundle-status.json',
        'delivery/rc/runtime-proof-results/administering-rc-bundle-status-summary.txt',
        'delivery/rc/runtime-proof-results/administering-rc-acceptance.json',
        'delivery/rc/runtime-proof-results/administering-rc-acceptance.txt',
    ];

    /** @var list<string> */
    private const README_REQUIRED_MARKERS = [
        'composer quality:rc-3rc',
        'composer rc:contract:validate',
        'administering:rc:contract:validate --json',
        'administering-rc-handoff-bundle-validation.json',
        'administering-rc-bundle-status.json',
        'administering-rc-acceptance.json',
        '3RC handoff accepted',
        'composer rc aliases invoke expected Symfony console commands',
    ];

    protected function configure(): void
    {
        $this
            ->addOption('composer-file', null, InputOption::VALUE_REQUIRED, 'Path to composer.json.', 'composer.json')
            ->addOption('manifest-file', null, InputOption::VALUE_REQUIRED, 'Path to delivery/rc/manifest.yaml.', 'delivery/rc/manifest.yaml')
            ->addOption('helper-file', null, InputOption::VALUE_REQUIRED, 'Path to the Windows runtime helper.', 'tools/runtime/capture-administering-rc-proof.ps1')
            ->addOption('readme-file', null, InputOption::VALUE_REQUIRED, 'Path to delivery/rc/README.adoc.', 'delivery/rc/README.adoc')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit a machine-readable contract report.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $composerFile = $this->pathOption($input->getOption('composer-file'));
        $manifestFile = $this->pathOption($input->getOption('manifest-file'));
        $helperFile = $this->pathOption($input->getOption('helper-file'));
        $readmeFile = $this->pathOption($input->getOption('readme-file'));

        $checks = [];
        $errors = [];

        $composer = $this->readComposer($composerFile, $checks, $errors);
        $manifest = $this->readManifest($manifestFile, $checks, $errors);
        $helper = $this->readText($helperFile, $checks, $errors, 'helper');
        $readme = $this->readText($readmeFile, $checks, $errors, 'readme');

        $scripts = is_array($composer) && is_array($composer['scripts'] ?? null) ? $composer['scripts'] : [];
        $qualityChain = $scripts['quality:rc-3rc'] ?? null;
        $this->addCheck($checks, $errors, 'composer_quality_rc_3rc_is_array', is_array($qualityChain), 'composer scripts.quality:rc-3rc must be an ordered array');
        if (is_array($qualityChain)) {
            $this->addCheck($checks, $errors, 'composer_quality_rc_3rc_expected_order', self::EXPECTED_CHAIN === array_values($qualityChain), 'quality:rc-3rc order matches canonical sequence');
        }

        foreach (self::REQUIRED_ALIASES as $alias) {
            $this->addCheck($checks, $errors, 'composer_alias_'.str_replace([':', '-'], '_', $alias), isset($scripts[$alias]), sprintf('composer script %s exists', $alias));
        }

        foreach (self::REQUIRED_ALIAS_COMMAND_MARKERS as $alias => $commandMarker) {
            $aliasCommand = $this->composerAliasCommand($scripts[$alias] ?? null);
            $this->addCheck(
                $checks,
                $errors,
                'composer_alias_command_marker_'.str_replace([':', '-'], '_', $alias),
                '' !== $aliasCommand && str_contains($aliasCommand, $commandMarker),
                sprintf('composer script %s invokes %s', $alias, $commandMarker),
            );
        }

        $composerAliasCommands = $this->composerAliasCommands($scripts);
        foreach (self::REQUIRED_ARTIFACTS as $artifactPath) {
            $this->addCheck(
                $checks,
                $errors,
                'composer_alias_mentions_'.$this->checkName($artifactPath),
                str_contains($composerAliasCommands, $artifactPath),
                sprintf('composer rc aliases mention %s as input or output', $artifactPath),
            );
        }

        if (is_array($manifest)) {
            $this->addCheck($checks, $errors, 'manifest_component', 'Administering' === ($manifest['component'] ?? null), 'component=Administering');
            $this->addCheck($checks, $errors, 'manifest_package', 'administering/admin' === ($manifest['package'] ?? null), 'package=administering/admin');
            $this->addCheck($checks, $errors, 'manifest_namespace', 'App\Administering' === ($manifest['namespace'] ?? null), 'namespace=App\Administering');

            $manifestComposerScripts = $manifest['composer_scripts'] ?? [];
            $this->addCheck($checks, $errors, 'manifest_composer_scripts_map', is_array($manifestComposerScripts), 'manifest composer_scripts map exists');
            if (is_array($manifestComposerScripts)) {
                $this->addCheck($checks, $errors, 'manifest_quality_rc_3rc_script', isset($manifestComposerScripts['quality_rc_3rc']), 'composer_scripts.quality_rc_3rc exists');
                foreach (self::REQUIRED_ALIASES as $alias) {
                    $manifestKey = $this->manifestComposerScriptKey($alias);
                    $expectedValue = 'composer '.$alias;
                    $actualValue = $manifestComposerScripts[$manifestKey] ?? null;
                    $this->addCheck(
                        $checks,
                        $errors,
                        'manifest_composer_script_'.$manifestKey,
                        $actualValue === $expectedValue,
                        sprintf('composer_scripts.%s must be %s', $manifestKey, $expectedValue),
                    );
                }
            }

            $manifestComposerAliases = $manifest['composer_aliases'] ?? [];
            $this->addCheck($checks, $errors, 'manifest_composer_aliases_map', is_array($manifestComposerAliases), 'manifest composer_aliases map exists');
            if (is_array($manifestComposerAliases)) {
                foreach (self::REQUIRED_ALIASES as $alias) {
                    $manifestKey = $this->manifestComposerScriptKey($alias);
                    $expectedValue = 'composer '.$alias;
                    $actualValue = $manifestComposerAliases[$manifestKey] ?? null;
                    $this->addCheck(
                        $checks,
                        $errors,
                        'manifest_composer_alias_'.$manifestKey,
                        $actualValue === $expectedValue,
                        sprintf('composer_aliases.%s must be %s', $manifestKey, $expectedValue),
                    );
                }
            }

            $staticContractValidation = $manifest['static_contract_validation'] ?? [];
            $this->addCheck($checks, $errors, 'manifest_static_contract_validation_map', is_array($staticContractValidation), 'manifest static_contract_validation map exists');
            if (is_array($staticContractValidation)) {
                $purpose = (string) ($staticContractValidation['purpose'] ?? '');
                $this->addCheck(
                    $checks,
                    $errors,
                    'manifest_static_contract_mentions_readme',
                    str_contains($purpose, 'README') || str_contains($purpose, 'README.adoc'),
                    'static_contract_validation purpose mentions README drift coverage',
                );

                $coverage = $staticContractValidation['coverage'] ?? [];
                $this->addCheck($checks, $errors, 'manifest_static_contract_coverage_list', is_array($coverage), 'static_contract_validation.coverage list exists');
                if (is_array($coverage)) {
                    $coverageValues = array_values(array_filter($coverage, 'is_string'));
                    foreach (self::REQUIRED_STATIC_CONTRACT_COVERAGE as $coverageMarker) {
                        $this->addCheck(
                            $checks,
                            $errors,
                            'manifest_static_contract_coverage_'.$this->checkName($coverageMarker),
                            in_array($coverageMarker, $coverageValues, true),
                            sprintf('static_contract_validation.coverage includes %s', $coverageMarker),
                        );
                    }
                }
            }

            $manifestArtifacts = $manifest['artifacts'] ?? [];
            $this->addCheck($checks, $errors, 'manifest_artifacts_map', is_array($manifestArtifacts), 'manifest artifacts map exists');
            if (is_array($manifestArtifacts)) {
                $artifactValues = array_values(array_filter($manifestArtifacts, 'is_string'));
                foreach (self::REQUIRED_ARTIFACTS as $artifactPath) {
                    $this->addCheck(
                        $checks,
                        $errors,
                        'manifest_artifact_'.$this->checkName($artifactPath),
                        in_array($artifactPath, $artifactValues, true),
                        sprintf('manifest artifacts include %s', $artifactPath),
                    );
                }
            }

            $helperWrites = $manifest['windows_helper']['writes'] ?? [];
            $this->addCheck($checks, $errors, 'manifest_windows_helper_writes_list', is_array($helperWrites), 'manifest windows_helper.writes list exists');
            if (is_array($helperWrites)) {
                foreach (self::REQUIRED_ARTIFACTS as $artifactPath) {
                    $this->addCheck(
                        $checks,
                        $errors,
                        'manifest_helper_writes_'.$this->checkName($artifactPath),
                        in_array($artifactPath, $helperWrites, true),
                        sprintf('windows_helper.writes include %s', $artifactPath),
                    );
                }
            }
        }

        if (is_string($helper)) {
            foreach (self::REQUIRED_ARTIFACTS as $artifactPath) {
                $windowsPath = str_replace('/', '\\', $artifactPath);
                $this->addCheck(
                    $checks,
                    $errors,
                    'helper_mentions_'.$this->checkName($artifactPath),
                    str_contains($helper, $windowsPath) || str_contains($helper, $artifactPath),
                    sprintf('helper mentions %s', $artifactPath),
                );
            }
        }

        if (is_string($readme)) {
            foreach (self::README_REQUIRED_MARKERS as $marker) {
                $this->addCheck(
                    $checks,
                    $errors,
                    'readme_mentions_'.$this->checkName($marker),
                    str_contains($readme, $marker),
                    sprintf('README mentions %s', $marker),
                );
            }
        }

        $valid = [] === $errors;
        $report = [
            'schema_version' => '1.0',
            'component' => 'Administering',
            'package' => 'administering/admin',
            'namespace' => 'App\Administering',
            'status' => $valid ? '3rc_contract_valid' : '3rc_contract_blocked',
            'contract_valid' => $valid,
            'checked_files' => [
                'composer' => $composerFile,
                'manifest' => $manifestFile,
                'helper' => $helperFile,
                'readme' => $readmeFile,
            ],
            'expected_chain' => self::EXPECTED_CHAIN,
            'required_artifacts' => self::REQUIRED_ARTIFACTS,
            'checks' => $checks,
            'errors' => $errors,
        ];

        $encoded = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (false === $encoded) {
            throw new \RuntimeException('Unable to encode RC contract validation report.');
        }

        if (true === $input->getOption('json')) {
            $output->writeln($encoded);
        } elseif ($valid) {
            $io->success('Administering 3RC static contract is valid.');
        } else {
            $io->error('Administering 3RC static contract is blocked.');
            foreach ($errors as $error) {
                $io->writeln('- '.$error);
            }
        }

        return $valid ? Command::SUCCESS : Command::FAILURE;
    }

    private function pathOption(mixed $value): string
    {
        if (!is_string($value) || '' === trim($value)) {
            throw new \InvalidArgumentException('Expected a non-empty path option.');
        }

        return $value;
    }

    /**
     * @param list<array{name: string, passed: bool, details: string}> $checks
     * @param list<string>                                             $errors
     *
     * @return array<string, mixed>|null
     */
    private function readComposer(string $path, array &$checks, array &$errors): ?array
    {
        $content = $this->readText($path, $checks, $errors, 'composer');
        if (null === $content) {
            return null;
        }

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            $this->addCheck($checks, $errors, 'composer_json_parse', false, $exception->getMessage());

            return null;
        }

        $this->addCheck($checks, $errors, 'composer_json_parse', is_array($decoded), $path);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param list<array{name: string, passed: bool, details: string}> $checks
     * @param list<string>                                             $errors
     *
     * @return array<string, mixed>|null
     */
    private function readManifest(string $path, array &$checks, array &$errors): ?array
    {
        $exists = is_file($path);
        $this->addCheck($checks, $errors, 'manifest_file_exists', $exists, $path);
        if (!$exists) {
            return null;
        }

        try {
            $decoded = Yaml::parseFile($path);
        } catch (\Throwable $exception) {
            $this->addCheck($checks, $errors, 'manifest_yaml_parse', false, $exception->getMessage());

            return null;
        }

        $this->addCheck($checks, $errors, 'manifest_yaml_parse', is_array($decoded), $path);

        return is_array($decoded) ? $decoded : null;
    }

    /** @param list<array{name:string,passed:bool,details:string}> $checks @param list<string> $errors */
    /**
     * @param list<array{name: string, passed: bool, details: string}> $checks
     * @param list<string>                                             $errors
     */
    private function readText(string $path, array &$checks, array &$errors, string $nameEntity): ?string
    {
        $exists = is_file($path);
        $this->addCheck($checks, $errors, $nameEntity.'_file_exists', $exists, $path);
        if (!$exists) {
            return null;
        }

        $content = file_get_contents($path);
        $readable = false !== $content;
        $this->addCheck($checks, $errors, $nameEntity.'_file_readable', $readable, $path);

        return is_string($content) ? $content : null;
    }

    /** @param list<array{name:string,passed:bool,details:string}> $checks @param list<string> $errors */
    /**
     * @param list<array{name: string, passed: bool, details: string}> $checks
     * @param list<string>                                             $errors
     */
    private function addCheck(array &$checks, array &$errors, string $nameEntity, bool $passed, string $details): void
    {
        $checks[] = [
            'nameEntity' => $nameEntity,
            'passed' => $passed,
            'details' => $details,
        ];

        if (!$passed) {
            $errors[] = sprintf('%s failed: %s', $nameEntity, $details);
        }
    }

    /** @param array<string, mixed> $scripts */
    private function composerAliasCommands(array $scripts): string
    {
        $commands = [];
        foreach (self::REQUIRED_ALIASES as $alias) {
            $aliasCommand = $this->composerAliasCommand($scripts[$alias] ?? null);
            if ('' !== $aliasCommand) {
                $commands[] = $aliasCommand;
            }
        }

        return implode('
', $commands);
    }

    private function composerAliasCommand(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (!is_array($value)) {
            return '';
        }

        $commands = [];
        foreach ($value as $entry) {
            if (is_string($entry)) {
                $commands[] = $entry;
            }
        }

        return implode('
', $commands);
    }

    private function manifestComposerScriptKey(string $alias): string
    {
        return str_replace([':', '-'], '_', $alias);
    }

    private function checkName(string $value): string
    {
        $nameEntity = strtolower(preg_replace('/[^A-Za-z0-9]+/', '_', $value) ?? $value);

        return trim($nameEntity, '_');
    }
}
