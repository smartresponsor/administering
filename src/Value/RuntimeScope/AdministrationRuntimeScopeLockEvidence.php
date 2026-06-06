<?php

declare(strict_types=1);

namespace App\Administering\Value\RuntimeScope;

final readonly class AdministrationRuntimeScopeLockEvidence
{
    /**
     * @param list<string> $enabledBundleTokens
     * @param list<string> $enabledComponents
     * @param list<string> $disabledComponents
     * @param list<string> $errors
     * @param list<string> $warnings
     */
    public function __construct(
        public string $path,
        public bool $present,
        public string $status,
        public ?string $sha256,
        public ?string $schema,
        public ?string $scope,
        public ?bool $strict,
        public ?string $sourceComposerFile,
        public ?string $sourceComposerSha256,
        public ?int $sourceComposerPackageCount,
        public ?string $generatedAt,
        public ?string $generatedBy,
        public array $enabledBundleTokens,
        public array $enabledComponents,
        public array $disabledComponents,
        public array $errors,
        public array $warnings,
    ) {
    }

    public function isValid(): bool
    {
        return [] === $this->errors;
    }

    /** @return list<string> */
    public function ageErrors(int $maxAgeSeconds): array
    {
        if ($maxAgeSeconds <= 0) {
            return [];
        }

        if (null === $this->generatedAt || '' === $this->generatedAt) {
            return ['Runtime scope lock generatedAt is missing while max-age validation is enabled.'];
        }

        try {
            $generatedAt = new \DateTimeImmutable($this->generatedAt);
        } catch (\Throwable) {
            return [sprintf('Runtime scope lock generatedAt is invalid: %s', $this->generatedAt)];
        }

        if ((time() - $generatedAt->getTimestamp()) > $maxAgeSeconds) {
            return [sprintf('Runtime scope lock is older than %d seconds.', $maxAgeSeconds)];
        }

        return [];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'path' => $this->path,
            'present' => $this->present,
            'status' => $this->status,
            'sha256' => $this->sha256,
            'schema' => $this->schema,
            'scope' => $this->scope,
            'strict' => $this->strict,
            'sourceComposerFile' => $this->sourceComposerFile,
            'sourceComposerSha256' => $this->sourceComposerSha256,
            'sourceComposerPackageCount' => $this->sourceComposerPackageCount,
            'generatedAt' => $this->generatedAt,
            'generatedBy' => $this->generatedBy,
            'enabledBundleTokens' => $this->enabledBundleTokens,
            'enabledComponents' => $this->enabledComponents,
            'disabledComponents' => $this->disabledComponents,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
        ];
    }
}
