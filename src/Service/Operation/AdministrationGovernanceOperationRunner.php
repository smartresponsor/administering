<?php

declare(strict_types=1);

namespace App\Administering\Service\Operation;

use App\Administering\ServiceInterface\Accessing\AdministrationAccountProjectionProviderInterface;
use App\Administering\ServiceInterface\Configuration\AdministrationConfigurationScannerInterface;
use App\Administering\ServiceInterface\Operation\AdministrationOperationArtifactWriterInterface;
use App\Administering\ServiceInterface\Operation\AdministrationOperationRunnerInterface;
use App\Administering\ServiceInterface\Rolling\AdministrationPermissionCatalogProviderInterface;
use App\Administering\Value\Operation\AdministrationOperationExecutionResult;
use App\Administering\Value\Operation\AdministrationOperationType;
use Symfony\Component\Process\Process;

/**
 * Runs safe metadata-only Administering operations that do not require secrets.
 */
final class AdministrationGovernanceOperationRunner implements AdministrationOperationRunnerInterface
{
    public function __construct(
        private readonly AdministrationConfigurationScannerInterface $configurationScanner,
        private readonly AdministrationPermissionCatalogProviderInterface $permissionCatalogProvider,
        private readonly AdministrationAccountProjectionProviderInterface $accountProjectionProvider,
        private readonly AdministrationOperationArtifactWriterInterface $artifactWriter,
        private readonly string $projectDir,
    ) {
    }

    /** @return list<string> */
    public function supportedOperationTypes(): array
    {
        return [
            AdministrationOperationType::CONFIGURATION_SCAN,
            AdministrationOperationType::CREDENTIAL_PRESENCE_CHECK,
            AdministrationOperationType::COMPOSER_VALIDATE,
            AdministrationOperationType::ROLLING_ACL_CATALOG_REFRESH,
            AdministrationOperationType::ACCESSING_ACCOUNT_ACTION,
        ];
    }

    public function run(string $operationKey, ?string $operationType = null): AdministrationOperationExecutionResult
    {
        $operationType ??= $operationKey;

        return match ($operationType) {
            AdministrationOperationType::CONFIGURATION_SCAN => $this->runConfigurationScan($operationKey),
            AdministrationOperationType::CREDENTIAL_PRESENCE_CHECK => $this->runCredentialPresenceCheck($operationKey),
            AdministrationOperationType::COMPOSER_VALIDATE => $this->runComposerValidate($operationKey),
            AdministrationOperationType::ROLLING_ACL_CATALOG_REFRESH => $this->runRollingCatalogRefresh($operationKey),
            AdministrationOperationType::ACCESSING_ACCOUNT_ACTION => $this->runAccessingProjectionRefresh($operationKey),
            default => AdministrationOperationExecutionResult::skipped(
                'Operation type is registered but has no concrete metadata runner yet.',
                ['operation_key' => $operationKey, 'operation_type' => $operationType],
            ),
        };
    }

    private function runConfigurationScan(string $operationKey): AdministrationOperationExecutionResult
    {
        $result = $this->configurationScanner->scan($this->projectDir);

        $safePayload = [
            'root' => $result->rootPath(),
            'entries' => count($result->entries()),
            'warnings' => count($result->warnings()),
        ];
        $artifact = $this->artifactWriter->writeJsonArtifact(
            $operationKey,
            'configuration-scan-summary',
            'Configuration scan summary',
            $safePayload,
        );

        return AdministrationOperationExecutionResult::succeeded(
            'Configuration scan completed as metadata-only operation.',
            $safePayload + ['artifact' => $artifact->relativePath()],
        );
    }

    private function runCredentialPresenceCheck(string $operationKey): AdministrationOperationExecutionResult
    {
        $result = $this->configurationScanner->scan($this->projectDir);
        $secretEntries = array_values(array_filter(
            $result->entries(),
            static fn ($entry): bool => $entry->secret(),
        ));

        $bySource = [];
        foreach ($secretEntries as $entry) {
            $bySource[$entry->sourceType()] = ($bySource[$entry->sourceType()] ?? 0) + 1;
        }
        ksort($bySource);

        $safePayload = [
            'credential_like_entries' => count($secretEntries),
            'source_types' => $bySource,
            'warnings' => count($result->warnings()),
        ];
        $artifact = $this->artifactWriter->writeJsonArtifact(
            $operationKey,
            'credential-presence-summary',
            'Credential presence summary',
            $safePayload,
        );

        return AdministrationOperationExecutionResult::succeeded(
            'Credential presence check completed without exposing credential values.',
            $safePayload + ['artifact' => $artifact->relativePath()],
        );
    }

    private function runComposerValidate(string $operationKey): AdministrationOperationExecutionResult
    {
        $composerJson = $this->projectDir.DIRECTORY_SEPARATOR.'composer.json';
        if (!is_file($composerJson)) {
            return AdministrationOperationExecutionResult::failed(
                'composer.json is missing at the configured project root.',
                ['project_root' => $this->projectDir],
            );
        }

        $process = new Process(['composer', 'validate', '--strict', '--no-check-publish'], $this->projectDir);
        $process->setTimeout(60);
        $process->run();

        $safePayload = [
            'exit_code' => $process->getExitCode(),
            'successful' => $process->isSuccessful(),
            'stdout_lines' => $this->safeProcessLines($process->getOutput()),
            'stderr_lines' => $this->safeProcessLines($process->getErrorOutput()),
        ];
        $artifact = $this->artifactWriter->writeJsonArtifact(
            $operationKey,
            'composer-validate-summary',
            'Composer validate summary',
            $safePayload,
        );

        if ($process->isSuccessful()) {
            return AdministrationOperationExecutionResult::succeeded(
                'Composer validation completed successfully.',
                $safePayload + ['artifact' => $artifact->relativePath()],
            );
        }

        return AdministrationOperationExecutionResult::failed(
            'Composer validation failed. See safe artifact summary for non-sensitive output.',
            $safePayload + ['artifact' => $artifact->relativePath()],
        );
    }

    /** @return list<string> */
    private function safeProcessLines(string $output): array
    {
        $normalized = preg_replace('/(secret|token|password|credential|private|authorization|dsn|key)=([^\s]+)/i', '$1=***', $output) ?? $output;
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', $normalized) ?: []), static fn (string $line): bool => '' !== $line));

        return array_slice($lines, 0, 25);
    }

    private function runRollingCatalogRefresh(string $operationKey): AdministrationOperationExecutionResult
    {
        $descriptors = $this->permissionCatalogProvider->descriptors();
        $categories = [];
        foreach ($descriptors as $descriptor) {
            $categories[$descriptor->category()] = true;
        }

        $safePayload = [
            'permissions' => count($descriptors),
            'categories' => count($categories),
        ];
        $artifact = $this->artifactWriter->writeJsonArtifact(
            $operationKey,
            'rolling-permission-catalog-summary',
            'Rolling permission catalog summary',
            $safePayload,
        );

        return AdministrationOperationExecutionResult::succeeded(
            'Rolling permission catalog refreshed from safe manifest metadata.',
            $safePayload + ['artifact' => $artifact->relativePath()],
        );
    }

    private function runAccessingProjectionRefresh(string $operationKey): AdministrationOperationExecutionResult
    {
        $projections = $this->accountProjectionProvider->recent(25);

        $safePayload = ['accounts' => count($projections)];
        $artifact = $this->artifactWriter->writeJsonArtifact(
            $operationKey,
            'accessing-account-projection-summary',
            'Accessing account projection summary',
            $safePayload,
        );

        return AdministrationOperationExecutionResult::succeeded(
            'Accessing account projections refreshed from safe metadata.',
            $safePayload + ['artifact' => $artifact->relativePath()],
        );
    }
}
