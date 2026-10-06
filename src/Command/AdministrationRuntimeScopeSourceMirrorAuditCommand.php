<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\Scanner\Admin\AdministrationRuntimeScopeSourceMirrorScanner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'administering:runtime-scope-source:mirror-audit',
    description: 'Audits runtime-scope source index routes against service-backed actions and EasyAdmin menu mirrors.',
)]
/**
 * Audits runtime-scope source routes for service-backed action coverage and matching EasyAdmin navigation mirrors.
 */
final class AdministrationRuntimeScopeSourceMirrorAuditCommand extends Command
{
    public function __construct(
        private readonly string $projectDir,
        private readonly AdministrationRuntimeScopeSourceMirrorScanner $scanner,
    ) {
        parent::__construct();
    }

    /**
     * Declares machine-readable output control for the runtime-scope source mirror audit.
     */
    protected function configure(): void
    {
        $this->addOption('json', null, InputOption::VALUE_NONE, 'Print machine-readable JSON report.');
    }

    /**
     * Scans source-route mirror coverage and reports route, service, menu, and issue evidence with clean/failure exit status.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $report = $this->scanner->scan($this->projectDir);

        if ((bool) $input->getOption('json')) {
            $output->writeln((string) json_encode($report->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $report->isClean() ? Command::SUCCESS : Command::FAILURE;
        }

        $output->writeln('Runtime-scope source mirror audit');
        $output->writeln('Routes: '.$report->toArray()['routeCount']);
        $output->writeln('Errors: '.$report->errorCount());
        $output->writeln('Warnings: '.$report->warningCount());

        foreach ($report->routes as $route) {
            $output->writeln(sprintf(
                '- %s -> %s | service=%s | menu=%s',
                $route['path'],
                $route['route'],
                true === $route['serviceBacked'] ? 'yes' : 'no',
                true === $route['easyAdminMenuMirrored'] ? 'yes' : 'no',
            ));
        }

        foreach ($report->issues as $issue) {
            $output->writeln(sprintf(
                '[%s] %s: %s',
                strtoupper($issue['severity']),
                $issue['code'],
                $issue['message'],
            ));
        }

        return $report->isClean() ? Command::SUCCESS : Command::FAILURE;
    }
}
