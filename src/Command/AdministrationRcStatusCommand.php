<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\Service\Rc\AdministrationRcStatusReportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'administering:rc:status',
    description: 'Summarizes captured Administering 3RC proof artifacts and final-seal validation status.',
)]
/**
 * Emits a compact read-only status summary for captured Administering 3RC artifacts.
 *
 * This command intentionally does not rerun proof and does not mutate Doctrine or
 * filesystem state except for the optional output JSON file. It is the operator
 * and watchdog-friendly inventory view after the full RC proof/final-seal chain
 * has already produced its artifacts.
 */
final class AdministrationRcStatusCommand extends Command
{
    public function __construct(private readonly AdministrationRcStatusReportService $statusReportService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('manifest-file', null, InputOption::VALUE_REQUIRED, 'Path to delivery/rc/manifest.yaml.', 'delivery/rc/manifest.yaml')
            ->addOption('proof-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-proof.json.', 'delivery/rc/runtime-proof-results/administering-rc-proof.json')
            ->addOption('index-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-proof-index.json.', 'delivery/rc/runtime-proof-results/administering-rc-proof-index.json')
            ->addOption('validation-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-proof-validation.json.', 'delivery/rc/runtime-proof-results/administering-rc-proof-validation.json')
            ->addOption('owner-review-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-owner-review.json.', 'delivery/rc/runtime-proof-results/administering-rc-owner-review.json')
            ->addOption('final-seal-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-final-seal.json.', 'delivery/rc/runtime-proof-results/administering-rc-final-seal.json')
            ->addOption('final-seal-validation-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-final-seal-validation.json.', 'delivery/rc/runtime-proof-results/administering-rc-final-seal-validation.json')
            ->addOption('receipt-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-receipt.json.', 'delivery/rc/runtime-proof-results/administering-rc-receipt.json')
            ->addOption('receipt-text-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-receipt.txt.', 'delivery/rc/runtime-proof-results/administering-rc-receipt.txt')
            ->addOption('receipt-validation-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-receipt-validation.json.', 'delivery/rc/runtime-proof-results/administering-rc-receipt-validation.json')
            ->addOption('handoff-index-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-handoff-index.json.', 'delivery/rc/runtime-proof-results/administering-rc-handoff-index.json')
            ->addOption('handoff-index-text-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-handoff-index.txt.', 'delivery/rc/runtime-proof-results/administering-rc-handoff-index.txt')
            ->addOption('handoff-index-validation-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-handoff-index-validation.json.', 'delivery/rc/runtime-proof-results/administering-rc-handoff-index-validation.json')
            ->addOption('final-status-validation-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-final-status-validation.json.', 'delivery/rc/runtime-proof-results/administering-rc-final-status-validation.json')
            ->addOption('handoff-bundle-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-handoff-bundle.json.', 'delivery/rc/runtime-proof-results/administering-rc-handoff-bundle.json')
            ->addOption('handoff-bundle-text-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-handoff-bundle.txt.', 'delivery/rc/runtime-proof-results/administering-rc-handoff-bundle.txt')
            ->addOption('handoff-bundle-validation-file', null, InputOption::VALUE_REQUIRED, 'Path to administering-rc-handoff-bundle-validation.json.', 'delivery/rc/runtime-proof-results/administering-rc-handoff-bundle-validation.json')
            ->addOption('include-receipt-artifacts', null, InputOption::VALUE_NONE, 'Also require and summarize the final receipt and receipt-validation artifacts.')
            ->addOption('include-handoff-artifacts', null, InputOption::VALUE_NONE, 'Also require and summarize the terminal handoff index and handoff-index-validation artifacts.')
            ->addOption('include-handoff-bundle-artifacts', null, InputOption::VALUE_NONE, 'Also require and summarize the terminal handoff bundle and handoff-bundle-validation artifacts.')
            ->addOption('output-file', null, InputOption::VALUE_REQUIRED, 'Optional path where the RC status JSON should be written.')
            ->addOption('summary-file', null, InputOption::VALUE_REQUIRED, 'Optional path where a compact human-readable RC status summary should be written.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit a machine-readable RC status report.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $manifestFile = $this->pathOption($input->getOption('manifest-file'));
        $proofFile = $this->pathOption($input->getOption('proof-file'));
        $indexFile = $this->pathOption($input->getOption('index-file'));
        $validationFile = $this->pathOption($input->getOption('validation-file'));
        $ownerReviewFile = $this->pathOption($input->getOption('owner-review-file'));
        $finalSealFile = $this->pathOption($input->getOption('final-seal-file'));
        $finalSealValidationFile = $this->pathOption($input->getOption('final-seal-validation-file'));
        $receiptFile = $this->pathOption($input->getOption('receipt-file'));
        $receiptTextFile = $this->pathOption($input->getOption('receipt-text-file'));
        $receiptValidationFile = $this->pathOption($input->getOption('receipt-validation-file'));
        $handoffIndexFile = $this->pathOption($input->getOption('handoff-index-file'));
        $handoffIndexTextFile = $this->pathOption($input->getOption('handoff-index-text-file'));
        $handoffIndexValidationFile = $this->pathOption($input->getOption('handoff-index-validation-file'));
        $finalStatusValidationFile = $this->pathOption($input->getOption('final-status-validation-file'));
        $handoffBundleFile = $this->pathOption($input->getOption('handoff-bundle-file'));
        $handoffBundleTextFile = $this->pathOption($input->getOption('handoff-bundle-text-file'));
        $handoffBundleValidationFile = $this->pathOption($input->getOption('handoff-bundle-validation-file'));
        $includeReceiptArtifacts = (bool) $input->getOption('include-receipt-artifacts');
        $includeHandoffArtifacts = (bool) $input->getOption('include-handoff-artifacts');
        $includeHandoffBundleArtifacts = (bool) $input->getOption('include-handoff-bundle-artifacts');
        if ($includeHandoffBundleArtifacts) {
            $includeReceiptArtifacts = true;
            $includeHandoffArtifacts = true;
        }
        $outputFile = $this->optionalPathOption($input->getOption('output-file'));
        $summaryFile = $this->optionalPathOption($input->getOption('summary-file'));

        $report = $this->statusReportService->buildReport(
            $manifestFile,
            $proofFile,
            $indexFile,
            $validationFile,
            $ownerReviewFile,
            $finalSealFile,
            $finalSealValidationFile,
            $receiptFile,
            $receiptTextFile,
            $receiptValidationFile,
            $handoffIndexFile,
            $handoffIndexTextFile,
            $handoffIndexValidationFile,
            $finalStatusValidationFile,
            $handoffBundleFile,
            $handoffBundleTextFile,
            $handoffBundleValidationFile,
            $includeReceiptArtifacts,
            $includeHandoffArtifacts,
            $includeHandoffBundleArtifacts,
        );

        return $this->publishReport($input, $output, $io, $report, $outputFile, $summaryFile);
    }

    /**
     * @param array<string, mixed> $report
     */
    private function publishReport(
        InputInterface $input,
        OutputInterface $output,
        SymfonyStyle $io,
        array $report,
        ?string $outputFile,
        ?string $summaryFile,
    ): int {
        $encodedReport = (string) json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (null !== $outputFile) {
            $this->writeJsonArtifact($outputFile, $encodedReport);
        }

        if (null !== $summaryFile) {
            $this->writeTextArtifact($summaryFile, $this->buildSummary($report));
        }

        $ready = true === ($report['sealed_3rc_validated'] ?? false);
        if ((bool) $input->getOption('json')) {
            $output->writeln($encodedReport);

            return $ready ? Command::SUCCESS : Command::FAILURE;
        }

        $io->title('Administering 3RC status');
        $io->definitionList(
            ['status' => $report['status']],
            ['component' => $report['component']],
            ['RC stage' => $report['rc_stage']],
            ['output file' => $outputFile ?? '(not written)'],
            ['summary file' => $summaryFile ?? '(not written)'],
        );

        $artifactStatus = is_array($report['artifact_status'] ?? null) ? $report['artifact_status'] : [];
        $io->table(['Artifact', 'Status'], array_map(
            static fn (string $nameEntity, mixed $status): array => [$nameEntity, is_scalar($status) ? (string) $status : '(missing)'],
            array_keys($artifactStatus),
            array_values($artifactStatus),
        ));

        $errors = is_array($report['errors'] ?? null) ? $report['errors'] : [];
        if ([] !== $errors) {
            $io->error($errors);

            return Command::FAILURE;
        }

        $io->success('Administering 3RC artifacts are captured, sealed, validated, and terminal handoff-ready.');

        return Command::SUCCESS;
    }

    /** @param array<string, mixed> $report */
    private function buildSummary(array $report): string
    {
        $lines = [
            'Administering 3RC Status',
            '=========================',
            '',
            sprintf('Status: %s', (string) ($report['status'] ?? 'unknown')),
            sprintf('Sealed 3RC validated: %s', true === ($report['sealed_3rc_validated'] ?? false) ? 'yes' : 'no'),
            sprintf('Component: %s', (string) ($report['component'] ?? 'Administering')),
            sprintf('Package: %s', (string) ($report['package'] ?? 'administering/admin')),
            sprintf('Namespace: %s', (string) ($report['namespace'] ?? 'App\\Administering')),
            sprintf('RC stage: %s', (string) ($report['rc_stage'] ?? '3RC-candidate')),
            sprintf('Reported at UTC: %s', (string) ($report['reported_at_utc'] ?? 'unknown')),
            '',
            'Artifact statuses:',
        ];

        $artifactStatus = $report['artifact_status'] ?? [];
        if (is_array($artifactStatus)) {
            foreach ($artifactStatus as $nameEntity => $status) {
                $lines[] = sprintf('- %s: %s', (string) $nameEntity, is_scalar($status) ? (string) $status : '(missing)');
            }
        }

        $lines[] = '';
        $lines[] = 'Artifact hashes:';
        $artifacts = $report['artifacts'] ?? [];
        if (is_array($artifacts)) {
            foreach ($artifacts as $nameEntity => $value) {
                if (str_ends_with((string) $nameEntity, '_sha256')) {
                    $lines[] = sprintf('- %s: %s', (string) $nameEntity, is_scalar($value) && '' !== (string) $value ? (string) $value : '(missing)');
                }
            }
        }

        $errors = $report['errors'] ?? [];
        $lines[] = '';
        $lines[] = 'Errors:';
        if ([] === $errors) {
            $lines[] = '- none';
        } elseif (is_array($errors)) {
            foreach ($errors as $error) {
                $lines[] = sprintf('- %s', is_scalar($error) ? (string) $error : json_encode($error));
            }
        }

        return implode("\n", $lines)."\n";
    }

    private function writeTextArtifact(string $file, string $contents): void
    {
        $directory = dirname($file);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        file_put_contents($file, $contents);
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

    private function writeJsonArtifact(string $file, string $contents): void
    {
        $directory = dirname($file);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        file_put_contents($file, $contents);
    }
}
