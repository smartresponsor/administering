<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\Service\RuntimeScope\AdministrationRuntimeScopeKernelRecipeService;
use App\Administering\Value\RuntimeScope\AdministrationRuntimeScopeKernelRecipeRequest;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'administering:runtime-scope:install-kernel-recipe',
    description: 'Installs the host Kernel runtime-scope reader files and optional Kernel hook for App-side bundle composition.',
)]
final class AdministrationRuntimeScopeKernelRecipeCommand extends Command
{
    public function __construct(
        private readonly string $projectDir,
        private readonly AdministrationRuntimeScopeKernelRecipeService $recipeService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('host-dir', null, InputOption::VALUE_REQUIRED, 'Host application root directory.', $this->projectDir)
            ->addOption('apply', null, InputOption::VALUE_NONE, 'Write the host recipe files. Without this option the command only reports the plan.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Overwrite existing recipe-owned helper/lock files. Kernel.php is never overwritten; it is patched with a backup when possible.')
            ->addOption('no-kernel-patch', null, InputOption::VALUE_NONE, 'Do not attempt to patch src/Kernel.php; only write helper and lock files.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print the recipe report as JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = $this->recipeService->install(new AdministrationRuntimeScopeKernelRecipeRequest(
            hostDir: (string) $input->getOption('host-dir'),
            apply: (bool) $input->getOption('apply'),
            force: (bool) $input->getOption('force'),
            patchKernel: !(bool) $input->getOption('no-kernel-patch'),
        ));

        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($result->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $result->isSuccessful() ? Command::SUCCESS : Command::FAILURE;
        }

        $io = new SymfonyStyle($input, $output);
        $io->section('Administration runtime-scope Kernel recipe');
        $io->writeln(sprintf('Host: <info>%s</info>', $result->hostDir));
        $io->writeln(sprintf('Mode: <info>%s</info>', $result->apply ? 'apply' : 'dry-run'));
        $io->writeln(sprintf('Composer default: <info>%s</info>', $result->composerInventory['default']['status']));
        $io->writeln(sprintf('Composer production: <info>%s</info>', $result->composerInventory['production']['status']));
        $io->table(
            ['Type', 'Path', 'Status'],
            array_map(static fn (array $action): array => [
                $action['type'] ?? 'unknown',
                $action['path'] ?? 'src/Kernel.php',
                $action['status'] ?? 'unknown',
            ], $result->actions),
        );

        if (!$result->isSuccessful()) {
            $io->error($result->errors);

            return Command::FAILURE;
        }

        $io->success($result->apply ? 'Runtime-scope Kernel recipe applied.' : 'Runtime-scope Kernel recipe plan is ready. Re-run with --apply to write files.');

        return Command::SUCCESS;
    }
}
