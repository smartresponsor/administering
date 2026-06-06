<?php

declare(strict_types=1);

namespace App\Administering\Service\RuntimeScope;

use App\Administering\Factory\RuntimeScope\AdministrationRuntimeScopePhpLockSourceFactory;
use App\Administering\Reader\RuntimeScope\AdministrationRuntimeScopeBundleCatalogReader;
use App\Administering\Resolver\RuntimeScope\AdministrationRuntimeScopePathResolver;
use App\Administering\Service\Connected\AdministrationConnectedComponentRecordSyncService;
use App\Administering\ServiceInterface\Audit\AdministrationAuditRecorderInterface;
use App\Administering\Value\Form\RuntimeScope\AdministrationRuntimeScopeComponentDecisionData;

final readonly class AdministrationRuntimeScopeComponentDecisionApplyService
{
    public function __construct(
        private string $projectDir,
        private AdministrationRuntimeScopePathResolver $pathResolver,
        private AdministrationRuntimeScopeBundleCatalogReader $catalogReader,
        private AdministrationRuntimeScopePhpLockSourceFactory $sourceFactory,
        private AdministrationConnectedComponentRecordSyncService $recordSyncService,
        private AdministrationAuditRecorderInterface $auditRecorder,
    ) {
    }

    /** @return array<string, mixed> */
    public function apply(AdministrationRuntimeScopeComponentDecisionData $data): array
    {
        $environment = $this->normalizeEnvironment($data->environment);
        $componentKey = $this->normalizeComponentKey($data->componentKey);
        $catalog = $this->catalogReader->catalog($this->pathResolver->defaultCatalogFile());
        $definition = $catalog['components'][$componentKey] ?? null;
        if (null === $definition) {
            throw new \InvalidArgumentException(sprintf('Unknown runtime-scope component: %s', $componentKey));
        }

        $lockPath = $this->pathResolver->lockPath($this->projectDir, $environment);
        $payload = $this->readPayload($lockPath, $environment);
        $before = [
            'enabledBundleTokens' => $this->stringList($payload['enabledBundleTokens'] ?? []),
            'disabledComponents' => $this->componentList($payload['disabledComponents'] ?? []),
        ];

        $bundleToken = $definition['bundleToken'];
        $enabledBundleTokens = $before['enabledBundleTokens'];
        $disabledComponents = $before['disabledComponents'];

        if ($data->enabled) {
            $enabledBundleTokens[] = $bundleToken;
            $disabledComponents = array_values(array_filter(
                $disabledComponents,
                static fn (string $component): bool => $component !== $componentKey,
            ));
        } else {
            $enabledBundleTokens = array_values(array_filter(
                $enabledBundleTokens,
                static fn (string $token): bool => $token !== $bundleToken,
            ));
            $disabledComponents[] = $componentKey;
        }

        $payload['enabledBundleTokens'] = $this->uniqueSorted($enabledBundleTokens);
        $payload['enabledComponents'] = $this->componentsFromBundleTokens($payload['enabledBundleTokens']);
        $payload['disabledComponents'] = $this->uniqueSorted($disabledComponents);
        $payload['generatedAt'] = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);
        $payload['generatedBy'] = 'administering.runtime_scope.component_decision';
        $payload['lastDecision'] = [
            'component' => $componentKey,
            'environment' => $environment,
            'enabled' => $data->enabled,
            'reason' => $data->reason,
            'decidedAt' => $payload['generatedAt'],
        ];

        $this->writePayload($lockPath, $payload);
        $this->recordSyncService->synchronize();
        $this->auditRecorder->record('runtime_scope.component_decision', $componentKey, [
            'environment' => $environment,
            'enabled' => $data->enabled,
            'reason' => $data->reason,
            'lockPath' => $lockPath,
            'bundleToken' => $bundleToken,
            'before' => $before,
            'after' => [
                'enabledBundleTokens' => $payload['enabledBundleTokens'],
                'disabledComponents' => $payload['disabledComponents'],
            ],
        ]);

        return [
            'component' => $componentKey,
            'environment' => $environment,
            'enabled' => $data->enabled,
            'lockPath' => $lockPath,
            'enabledBundleTokens' => $payload['enabledBundleTokens'],
            'disabledComponents' => $payload['disabledComponents'],
        ];
    }

    /** @return array<string, mixed> */
    private function readPayload(string $lockPath, string $environment): array
    {
        if (is_file($lockPath)) {
            $payload = require $lockPath;
            if (!is_array($payload)) {
                throw new \RuntimeException(sprintf('Runtime scope lock must return an array: %s', $lockPath));
            }

            return $payload;
        }

        return [
            'schema' => 'app.kernel.runtime_scope.v1',
            'scope' => 'prod' === $environment ? 'production' : 'default',
            'environment' => $environment,
            'source' => 'materialized by Administering Enabled Components decision form',
            'sourceComposerFile' => $this->pathResolver->composerFile($environment),
            'sourceComposerSha256' => null,
            'sourceComposerPackageCount' => null,
            'strict' => 'prod' === $environment,
            'generatedAt' => null,
            'generatedBy' => null,
            'enabledComponents' => [],
            'enabledBundleTokens' => [],
            'disabledComponents' => [],
            'skippedComponents' => [],
        ];
    }

    /** @param array<string, mixed> $payload */
    private function writePayload(string $lockPath, array $payload): void
    {
        $directory = dirname($lockPath);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Unable to create runtime lock directory: %s', $directory));
        }

        file_put_contents($lockPath, $this->sourceFactory->source($payload));
    }

    /** @return list<string> */
    private function stringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return $this->uniqueSorted(array_values(array_filter(
            array_map(static fn (mixed $item): ?string => is_string($item) ? strtolower(trim($item)) : null, $value),
            static fn (?string $item): bool => null !== $item && '' !== $item,
        )));
    }

    /** @return list<string> */
    private function componentList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return $this->uniqueSorted(array_values(array_filter(
            array_map(static fn (mixed $item): ?string => is_string($item) ? self::normalizeStaticComponent($item) : null, $value),
            static fn (?string $item): bool => null !== $item && '' !== $item,
        )));
    }

    /** @param list<string> $bundleTokens */
    private function componentsFromBundleTokens(array $bundleTokens): array
    {
        return $this->uniqueSorted(array_map(
            static fn (string $token): string => self::normalizeStaticComponent(str_replace('.bundle', '', $token)),
            $bundleTokens,
        ));
    }

    /** @param list<string> $values */
    private function uniqueSorted(array $values): array
    {
        $values = array_values(array_unique(array_filter($values, static fn (string $value): bool => '' !== $value)));
        sort($values);

        return $values;
    }

    private function normalizeEnvironment(string $environment): string
    {
        $environment = strtolower(trim($environment));
        if (!in_array($environment, ['dev', 'prod'], true)) {
            throw new \InvalidArgumentException('Runtime component decision supports only dev and prod environments.');
        }

        return $environment;
    }

    private function normalizeComponentKey(string $componentKey): string
    {
        $componentKey = self::normalizeStaticComponent($componentKey);
        if (!preg_match('/^[a-z][a-z0-9-]*$/', $componentKey)) {
            throw new \InvalidArgumentException(sprintf('Invalid runtime-scope component key: %s', $componentKey));
        }

        return $componentKey;
    }

    private static function normalizeStaticComponent(string $componentKey): string
    {
        return strtolower(str_replace('_', '-', trim($componentKey)));
    }
}
