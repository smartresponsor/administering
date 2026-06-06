<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\Resolver\RuntimeScope\AdministrationRuntimeScopePathResolver;
use App\Administering\Service\RuntimeScope\AdministrationRuntimeScopeExportService;
use App\Administering\Service\RuntimeScope\AdministrationRuntimeScopeOutputSchemaService;
use App\Administering\Value\RuntimeScope\AdministrationRuntimeScopeExportRequest;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'administering:runtime-scope:export',
    description: 'Materializes App Kernel runtime-scope lock files from composer.json/composer.prod.json inventory and Administering runtime-scope token catalog.',
)]
final class AdministrationRuntimeScopeExportCommand extends Command
{
    public function __construct(
        private readonly string $projectDir,
        private readonly AdministrationRuntimeScopePathResolver $pathResolver,
        private readonly AdministrationRuntimeScopeExportService $exportService,
        private readonly AdministrationRuntimeScopeOutputSchemaService $outputSchema,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('host-dir', null, InputOption::VALUE_REQUIRED, 'Host application root directory.', $this->projectDir)
            ->addOption('env', null, InputOption::VALUE_REQUIRED, 'Target environment. prod writes runtime_scope.prod.lock.php, any other env writes runtime_scope.lock.php.', 'prod')
            ->addOption('scope', null, InputOption::VALUE_REQUIRED, 'Human-readable scope label stored in the lock.', null)
            ->addOption('catalog-file', null, InputOption::VALUE_REQUIRED, 'Administering-owned runtime-scope bundle catalog file.', null)
            ->addOption('enable-component', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Force-enable a known component key when its package exists in the selected composer inventory.')
            ->addOption('disable-component', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Force-disable a known component key.')
            ->addOption('skip-missing-packages', null, InputOption::VALUE_NONE, 'Skip enabled components whose package is not present in selected composer inventory instead of failing.')
            ->addOption('strict', null, InputOption::VALUE_NEGATABLE, 'Whether Kernel must fail when enabled bundle tokens/fingerprints are missing or stale. Defaults to true for prod, false otherwise.', null)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Print the lock payload without writing the file.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print the normalized runtime-scope output schema.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $environment = (string) $input->getOption('env');
        $strictOption = $input->getOption('strict');

        try {
            $result = $this->exportService->export(new AdministrationRuntimeScopeExportRequest(
                hostDir: (string) $input->getOption('host-dir'),
                environment: $environment,
                scope: (string) ($input->getOption('scope') ?: ('prod' === $environment ? 'prod-composer-inventory' : 'default-composer-inventory')),
                catalogFile: (string) ($input->getOption('catalog-file') ?: $this->pathResolver->defaultCatalogFile()),
                strict: is_bool($strictOption) ? $strictOption : ('prod' === $environment),
                skipMissingPackages: (bool) $input->getOption('skip-missing-packages'),
                dryRun: (bool) $input->getOption('dry-run'),
                forceEnable: $this->normalizedComponentList($input->getOption('enable-component')),
                forceDisable: $this->normalizedComponentList($input->getOption('disable-component')),
            ));
        } catch (\Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $payload = $this->outputSchema->exportPayload($result);

        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return Command::SUCCESS;
        }

        if ((bool) $input->getOption('dry-run')) {
            $io->section('Runtime-scope export dry-run');
            $io->writeln(sprintf('Target lock: <info>%s</info>', $result->lockPath));
            $io->writeln($result->source);

            return Command::SUCCESS;
        }

        $io->success(sprintf(
            'Runtime scope lock exported: %s (%d enabled bundle tokens, %d disabled components).',
            $result->lockPath,
            $result->enabledBundleTokenCount(),
            $result->disabledComponentCount(),
        ));

        return Command::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function normalizedComponentList(mixed $values): array
    {
        if (!is_array($values)) {
            return [];
        }

        $components = [];
        foreach ($values as $value) {
            if (!is_string($value)) {
                continue;
            }

            $component = strtolower(trim($value));
            if ('' !== $component) {
                $components[] = $component;
            }
        }

        return array_values(array_unique($components));
    }
}
