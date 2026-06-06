<?php

declare(strict_types=1);

namespace App\Administering\Service\RuntimeScope;

use App\Administering\Value\RuntimeScope\AdministrationRuntimeScopeLockEvidence;
use App\Administering\Value\RuntimeScope\AdministrationRuntimeScopeVisibility;

final readonly class AdministrationRuntimeScopeLockNormalizer
{
    public function normalize(string $lockPath): AdministrationRuntimeScopeLockEvidence
    {
        if (!is_file($lockPath)) {
            return new AdministrationRuntimeScopeLockEvidence(
                path: $lockPath,
                present: false,
                status: 'missing',
                sha256: null,
                schema: null,
                scope: null,
                strict: null,
                sourceComposerFile: null,
                sourceComposerSha256: null,
                sourceComposerPackageCount: null,
                generatedAt: null,
                generatedBy: null,
                enabledBundleTokens: [],
                enabledComponents: [],
                disabledComponents: [],
                errors: [sprintf('Runtime scope lock is missing: %s', $lockPath)],
                warnings: [],
            );
        }

        $sha256 = hash_file('sha256', $lockPath) ?: null;
        try {
            $payload = require $lockPath;
            if (!is_array($payload)) {
                throw new \RuntimeException('Runtime scope lock must return an array.');
            }
        } catch (\Throwable $exception) {
            return new AdministrationRuntimeScopeLockEvidence(
                path: $lockPath,
                present: true,
                status: 'unreadable',
                sha256: $sha256,
                schema: null,
                scope: null,
                strict: null,
                sourceComposerFile: null,
                sourceComposerSha256: null,
                sourceComposerPackageCount: null,
                generatedAt: null,
                generatedBy: null,
                enabledBundleTokens: [],
                enabledComponents: [],
                disabledComponents: [],
                errors: [sprintf('Unable to read runtime scope lock %s: %s', $lockPath, $exception->getMessage())],
                warnings: [],
            );
        }

        $errors = [];
        $warnings = [];
        $schema = is_string($payload['schema'] ?? null) ? $payload['schema'] : null;
        if ('app.kernel.runtime_scope.v1' !== $schema) {
            $errors[] = 'Runtime scope lock schema mismatch; expected app.kernel.runtime_scope.v1.';
        }

        if (array_key_exists('enabledComponents', $payload) && !array_key_exists('enabledBundleTokens', $payload)) {
            $warnings[] = 'Runtime scope lock uses legacy enabledComponents; regenerate lock to enabledBundleTokens.';
        }

        $enabledBundleTokens = $this->bundleTokens($payload['enabledBundleTokens'] ?? ($payload['enabledComponents'] ?? []), $errors, $warnings);
        $enabledComponents = $this->componentsFromBundleTokens($enabledBundleTokens);
        $disabledComponents = $this->components($payload['disabledComponents'] ?? [], $errors, 'disabledComponents');

        return new AdministrationRuntimeScopeLockEvidence(
            path: $lockPath,
            present: true,
            status: [] === $errors ? 'present' : 'invalid',
            sha256: $sha256,
            schema: $schema,
            scope: is_string($payload['scope'] ?? null) ? $payload['scope'] : null,
            strict: is_bool($payload['strict'] ?? null) ? $payload['strict'] : null,
            sourceComposerFile: is_string($payload['sourceComposerFile'] ?? null) ? $payload['sourceComposerFile'] : null,
            sourceComposerSha256: is_string($payload['sourceComposerSha256'] ?? null) ? $payload['sourceComposerSha256'] : null,
            sourceComposerPackageCount: is_int($payload['sourceComposerPackageCount'] ?? null) ? $payload['sourceComposerPackageCount'] : null,
            generatedAt: is_string($payload['generatedAt'] ?? null) ? $payload['generatedAt'] : null,
            generatedBy: is_string($payload['generatedBy'] ?? null) ? $payload['generatedBy'] : null,
            enabledBundleTokens: $enabledBundleTokens,
            enabledComponents: $enabledComponents,
            disabledComponents: $disabledComponents,
            errors: array_values(array_unique($errors)),
            warnings: array_values(array_unique($warnings)),
        );
    }

    /**
     * @param list<string> $errors
     * @param list<string> $warnings
     *
     * @return list<string>
     */
    private function bundleTokens(mixed $payload, array &$errors, array &$warnings): array
    {
        if (!is_array($payload)) {
            $errors[] = 'enabledBundleTokens must be an array.';

            return [];
        }

        $tokens = [];
        foreach ($payload as $item) {
            if (!is_string($item) || '' === trim($item)) {
                $errors[] = 'enabledBundleTokens contains an invalid token string.';
                continue;
            }

            $token = strtolower(trim($item));
            if (str_contains($token, '\\')) {
                $errors[] = sprintf('enabledBundleTokens must not contain PHP class names: %s', $item);
                continue;
            }

            if (!preg_match('/^[a-z][a-z0-9-]*\.bundle$/', $token)) {
                $warnings[] = sprintf('Enabled bundle token does not follow component.bundle shape: %s', $token);
            }

            $tokens[] = $token;
        }

        sort($tokens);

        return array_values(array_unique($tokens));
    }

    /**
     * @param list<string> $errors
     *
     * @return list<string>
     */
    private function components(mixed $payload, array &$errors, string $field): array
    {
        if (!is_array($payload)) {
            $errors[] = sprintf('%s must be an array.', $field);

            return [];
        }

        $components = [];
        foreach ($payload as $item) {
            if (!is_string($item) || '' === trim($item)) {
                $errors[] = sprintf('%s contains an invalid component token.', $field);
                continue;
            }

            if (str_contains($item, '\\')) {
                $errors[] = sprintf('%s must not contain PHP class names: %s', $field, $item);
                continue;
            }

            $components[] = AdministrationRuntimeScopeVisibility::normalizeComponent($item);
        }

        sort($components);

        return array_values(array_unique(array_filter($components)));
    }

    /**
     * @param list<string> $bundleTokens
     *
     * @return list<string>
     */
    private function componentsFromBundleTokens(array $bundleTokens): array
    {
        $components = [];
        foreach ($bundleTokens as $bundleToken) {
            $components[] = AdministrationRuntimeScopeVisibility::normalizeComponent(str_replace('.bundle', '', $bundleToken));
        }

        sort($components);

        return array_values(array_unique(array_filter($components)));
    }
}
