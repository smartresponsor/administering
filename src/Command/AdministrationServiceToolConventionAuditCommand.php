<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\ServiceInterface\Admin\AdministrationServiceToolConventionAuditorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'administering:service-tools:convention-audit',
    description: 'Audits src/Service/<Direction> files against the canonical Administering service-tool naming convention.',
)]
final class AdministrationServiceToolConventionAuditCommand extends Command
{
    public function __construct(private readonly AdministrationServiceToolConventionAuditorInterface $auditor)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('section', InputArgument::OPTIONAL, 'Optional section key to audit, for example Connected or Symfony.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print violations as JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $section = $input->getArgument('section');
        $violations = $this->auditor->violations(is_string($section) && '' !== $section ? $section : null);

        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode(array_map(static fn ($violation): array => $violation->toArray(), $violations), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

            return [] === $violations ? Command::SUCCESS : Command::FAILURE;
        }

        $io = new SymfonyStyle($input, $output);
        if ([] === $violations) {
            $io->success('All src/Service files satisfy the Administering service-tool convention.');

            return Command::SUCCESS;
        }

        $io->warning(sprintf('%d src/Service file(s) are not valid Administering service tools.', count($violations)));
        $io->table(
            ['Section', 'File', 'Reason', 'Suggestion', 'Suggested path'],
            array_map(static fn ($violation): array => [
                $violation->section,
                $violation->serviceFile,
                $violation->reason,
                $violation->suggestedAction,
                $violation->suggestedPath ?? '',
            ], $violations),
        );
        $io->writeln('Move helper/provider/definition classes out of src/Service or rename true tools to Administration{Direction}{ToolSlug}Service.');

        return Command::FAILURE;
    }
}
