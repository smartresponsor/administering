<?php

declare(strict_types=1);

namespace App\Administering\Service\RuntimeScope;

use App\Administering\Value\RuntimeScope\AdministrationRuntimeScopeDecision;
use App\Administering\Value\RuntimeScope\AdministrationRuntimeScopeExportResult;

/**
 * Builds the single machine-readable runtime-scope output schema used by commands and JSON surfaces.
 */
final readonly class AdministrationRuntimeScopeOutputSchemaService
{
    public const SCHEMA = 'administering.runtime_scope.output.v1';

    /**
     * @param array<string, mixed> $extra
     * @param list<string>|null    $errors
     * @param list<string>         $warnings
     *
     * @return array<string, mixed>
     */
    public function decisionPayload(
        string $report,
        AdministrationRuntimeScopeDecision $decision,
        array $extra = [],
        ?array $errors = null,
        array $warnings = [],
    ): array {
        return array_merge([
            'schema' => self::SCHEMA,
            'report' => $report,
            'source' => $decision->sourceSummary(),
            'components' => $decision->componentRows(),
            'errors' => $errors ?? $decision->sourceErrors(),
            'warnings' => $warnings,
        ], $extra);
    }

    /** @return array<string, mixed> */
    public function exportPayload(AdministrationRuntimeScopeExportResult $result): array
    {
        $payload = $result->payload;
        $enabledComponents = $this->stringList($payload['enabledComponents'] ?? []);
        $disabledComponents = $this->stringList($payload['disabledComponents'] ?? []);
        $skippedComponents = $this->stringList($payload['skippedComponents'] ?? []);
        $enabledBundleTokens = $this->stringList($payload['enabledBundleTokens'] ?? []);

        return [
            'schema' => self::SCHEMA,
            'report' => 'administration_runtime_scope_export',
            'source' => [
                'lockPath' => $result->lockPath,
                'environment' => $this->nullableString($payload['environment'] ?? null),
                'scope' => $this->nullableString($payload['scope'] ?? null),
                'sourceComposerFile' => $this->nullableString($payload['sourceComposerFile'] ?? null),
                'sourceComposerSha256' => $this->nullableString($payload['sourceComposerSha256'] ?? null),
                'sourceComposerPackageCount' => $this->intValue($payload['sourceComposerPackageCount'] ?? 0),
                'generatedAt' => $this->nullableString($payload['generatedAt'] ?? null),
                'generatedBy' => $this->nullableString($payload['generatedBy'] ?? null),
                'strict' => true === ($payload['strict'] ?? false),
            ],
            'components' => $this->exportComponentRows($enabledComponents, $disabledComponents, $skippedComponents),
            'export' => [
                'enabledComponents' => $enabledComponents,
                'enabledBundleTokens' => $enabledBundleTokens,
                'disabledComponents' => $disabledComponents,
                'skippedComponents' => $skippedComponents,
                'lockPayload' => $payload,
            ],
            'errors' => [],
            'warnings' => [],
        ];
    }

    /**
     * @param list<string> $enabledComponents
     * @param list<string> $disabledComponents
     * @param list<string> $skippedComponents
     *
     * @return list<array<string, mixed>>
     */
    private function exportComponentRows(array $enabledComponents, array $disabledComponents, array $skippedComponents): array
    {
        $components = array_values(array_unique(array_merge($enabledComponents, $disabledComponents, $skippedComponents)));
        sort($components);

        $rows = [];
        foreach ($components as $component) {
            $enabled = in_array($component, $enabledComponents, true);
            $skipped = in_array($component, $skippedComponents, true);
            $disabled = in_array($component, $disabledComponents, true) || $skipped;

            $rows[] = [
                'component' => $component,
                'present' => $enabled,
                'allowed' => $enabled,
                'locked' => $enabled,
                'enabled' => $enabled,
                'disabled' => $disabled,
                'status' => $enabled ? 'available' : ($skipped ? 'missing_package' : 'out_of_scope'),
                'reason' => $enabled
                    ? 'Component was materialized as enabled by runtime-scope export.'
                    : ($skipped ? 'Component was skipped because its package was missing during export.' : 'Component was materialized as disabled by runtime-scope export.'),
                'message' => $enabled
                    ? 'Component was materialized as enabled by runtime-scope export.'
                    : ($skipped ? 'Component was skipped because its package was missing during export.' : 'Component was materialized as disabled by runtime-scope export.'),
                'inRuntimeScope' => $enabled,
                'composerPackageInstalled' => $enabled,
                'lockEnabled' => $enabled,
                'lockDisabled' => $disabled,
                'runtimeScope' => null,
                'composerPackage' => null,
                'bundleToken' => null,
                'evidence' => [
                    'source' => 'runtime_scope_export_payload',
                    'skipped' => $skipped,
                ],
            ];
        }

        return $rows;
    }

    /** @return list<string> */
    private function stringList(mixed $values): array
    {
        if (!is_array($values)) {
            return [];
        }

        $strings = [];
        foreach ($values as $value) {
            if (is_scalar($value) && '' !== trim((string) $value)) {
                $strings[] = trim((string) $value);
            }
        }

        return array_values(array_unique($strings));
    }

    private function nullableString(mixed $value): ?string
    {
        if (null === $value) {
            return null;
        }

        return (string) $value;
    }

    private function intValue(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return 0;
    }
}
