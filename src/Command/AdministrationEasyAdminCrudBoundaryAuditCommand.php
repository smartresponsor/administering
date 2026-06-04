<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\Scanner\Admin\AdministrationEasyAdminCrudBoundaryScanner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'administering:ea-crud:boundary-audit',
    description: 'Audits EasyAdmin CRUD boundaries: native CRUD templates, SQLite/system entities, and Symfony-form-safe actions.'
)]
final class AdministrationEasyAdminCrudBoundaryAuditCommand extends Command
{
    public function __construct(
        private readonly AdministrationEasyAdminCrudBoundaryScanner $scanner,
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('json', null, InputOption::VALUE_NONE, 'Print machine-readable JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $report = $this->scanner->scan($this->projectDir);

        if ((bool) $input->getOption('json')) {
            $output->writeln((string) json_encode($report->toArray(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

            return $report->hasErrors() ? Command::FAILURE : Command::SUCCESS;
        }

        $output->writeln('EasyAdmin CRUD boundary audit');
        $output->writeln('');

        foreach ($report->items() as $item) {
            $output->writeln(sprintf(
                '- %s: native_template=%s sqlite_system=%s symfony_form_safe=%s',
                $item['controller'],
                $item['nativeCrudTemplate'] ? 'yes' : 'no',
                $item['sqliteSystemTable'] ? 'yes' : 'no',
                $item['symfonyFormSafe'] ? 'yes' : 'no',
            ));
        }

        foreach ($report->warnings() as $warning) {
            $output->writeln('<comment>WARNING '.$warning.'</comment>');
        }

        foreach ($report->errors() as $error) {
            $output->writeln('<error>ERROR '.$error.'</error>');
        }

        return $report->hasErrors() ? Command::FAILURE : Command::SUCCESS;
    }
}
