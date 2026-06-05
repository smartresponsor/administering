<?php

declare(strict_types=1);

namespace App\Administering\Scanner\Configuration;

use App\Administering\ServiceInterface\Configuration\AdministrationConfigurationScannerInterface;
use App\Administering\Value\Configuration\AdministrationConfigurationEntry;
use App\Administering\Value\Configuration\AdministrationConfigurationScanResult;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

final class AdministrationFilesystemConfigurationScanner implements AdministrationConfigurationScannerInterface
{
    /** @var list<string> */
    private const CONFIG_FILE_NAMES = [
        'composer.json',
        '.gitignore',
        '.env',
        '.env.local',
        'phpstan.neon',
        'phpunit.xml.dist',
        'rector.php',
    ];

    public function scan(string $hostRoot): AdministrationConfigurationScanResult
    {
        $root = rtrim($hostRoot, DIRECTORY_SEPARATOR);
        if (!is_dir($root)) {
            return new AdministrationConfigurationScanResult($hostRoot, [], ['Host root does not exist or is not a directory.']);
        }

        $entries = [];
        $warnings = [];

        foreach (self::CONFIG_FILE_NAMES as $fileName) {
            $path = $root.DIRECTORY_SEPARATOR.$fileName;
            if (!is_file($path)) {
                continue;
            }

            foreach ($this->entriesForFile($root, $path, null) as $entry) {
                $entries[] = $entry;
            }
        }

        foreach ($this->findYamlFiles($root) as $path) {
            try {
                foreach ($this->entriesForFile($root, $path, $this->guessComponentName($path)) as $entry) {
                    $entries[] = $entry;
                }
            } catch (\Throwable $exception) {
                $warnings[] = sprintf('Could not parse %s: %s', $this->relativePath($root, $path), $exception->getMessage());
            }
        }

        return new AdministrationConfigurationScanResult($root, $entries, $warnings);
    }

    /** @return list<string> */
    private function findYamlFiles(string $root): array
    {
        $directories = [];
        foreach (['config', 'config/component', 'config/packages'] as $relativeDirectory) {
            $directory = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativeDirectory);
            if (is_dir($directory)) {
                $directories[] = $directory;
            }
        }

        if ([] === $directories) {
            return [];
        }

        $finder = new Finder();
        $finder->files()->in($directories)->name(['*.yaml', '*.yml'])->ignoreUnreadableDirs();

        $files = [];
        foreach ($finder as $file) {
            $files[] = $file->getPathname();
        }

        sort($files);

        return $files;
    }

    /** @return list<AdministrationConfigurationEntry> */
    private function entriesForFile(string $root, string $path, ?string $componentName): array
    {
        $relativePath = $this->relativePath($root, $path);
        $checksum = hash_file('sha256', $path) ?: '';
        $sourceType = $this->sourceTypeFor($relativePath);

        $base = [new AdministrationConfigurationEntry(
            'file:'.$relativePath,
            $sourceType,
            $relativePath,
            $componentName,
            $checksum,
            false,
            $this->isDirectlyWritable($relativePath),
            ['checksum' => $checksum, 'size' => filesize($path) ?: 0],
        )];

        if ('composer.json' === basename($path)) {
            return array_merge($base, $this->composerEntries($relativePath, $path));
        }

        if (str_starts_with(basename($path), '.env')) {
            return array_merge($base, $this->envEntries($relativePath, $path));
        }

        if (preg_match('/\.ya?ml$/', $path)) {
            return array_merge($base, $this->yamlEntries($relativePath, $path, $componentName));
        }

        return $base;
    }

    /** @return list<AdministrationConfigurationEntry> */
    private function composerEntries(string $relativePath, string $path): array
    {
        $decoded = json_decode((string) file_get_contents($path), true);
        if (!is_array($decoded)) {
            return [];
        }

        $entries = [];
        foreach (['name', 'type', 'license', 'minimum-stability'] as $key) {
            if (array_key_exists($key, $decoded)) {
                $entries[] = new AdministrationConfigurationEntry(
                    'composer.'.$key,
                    'composer',
                    $relativePath,
                    null,
                    is_scalar($decoded[$key]) ? (string) $decoded[$key] : null,
                    false,
                    false,
                    ['apply_mode' => 'change_request'],
                );
            }
        }

        return $entries;
    }

    /** @return list<AdministrationConfigurationEntry> */
    private function envEntries(string $relativePath, string $path): array
    {
        $entries = [];
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ('' === $trimmed || str_starts_with($trimmed, '#') || !str_contains($trimmed, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $trimmed, 2);
            $key = trim($key);
            if ('' === $key) {
                continue;
            }

            $secret = $this->looksSecret($key);
            $entries[] = new AdministrationConfigurationEntry(
                'env.'.$key,
                'env',
                $relativePath,
                null,
                $secret ? '********' : trim($value, " \t\n\r\0\x0B\"'"),
                $secret,
                '.env.local' === basename($path),
                ['apply_mode' => $secret ? 'symfony_secrets' : 'change_request'],
            );
        }

        return $entries;
    }

    /** @return list<AdministrationConfigurationEntry> */
    private function yamlEntries(string $relativePath, string $path, ?string $componentName): array
    {
        $parsed = Yaml::parseFile($path);
        if (!is_array($parsed)) {
            return [];
        }

        $entries = [];
        foreach (array_keys($parsed) as $key) {
            if (!is_string($key)) {
                continue;
            }

            $entries[] = new AdministrationConfigurationEntry(
                'yaml.'.$relativePath.':'.$key,
                'symfony_config',
                $relativePath,
                $componentName,
                '[section]',
                false,
                str_starts_with($relativePath, 'config/packages/admin_generated/'),
                ['top_level_key' => $key, 'apply_mode' => 'change_request'],
            );
        }

        return $entries;
    }

    private function sourceTypeFor(string $relativePath): string
    {
        return match (true) {
            'composer.json' === $relativePath => 'composer',
            str_starts_with(basename($relativePath), '.env') => 'env',
            '.gitignore' === $relativePath => 'gitignore',
            1 === preg_match('/\.ya?ml$/', $relativePath) => 'symfony_config',
            default => 'file',
        };
    }

    private function isDirectlyWritable(string $relativePath): bool
    {
        return str_starts_with($relativePath, 'var/administering/')
            || str_starts_with($relativePath, 'config/packages/admin_generated/')
            || '.env.local' === basename($relativePath);
    }

    private function looksSecret(string $key): bool
    {
        return 1 === preg_match('/(SECRET|TOKEN|PASSWORD|PASS|KEY|DSN|CREDENTIAL)/i', $key);
    }

    private function guessComponentName(string $path): ?string
    {
        if (false !== stripos($path, 'accessing')) {
            return 'Accessing';
        }

        if (false !== stripos($path, 'rolling')) {
            return 'Rolling';
        }

        if (false !== stripos($path, 'administering')) {
            return 'Administering';
        }

        return null;
    }

    private function relativePath(string $root, string $path): string
    {
        $relative = ltrim(str_replace($root, '', $path), DIRECTORY_SEPARATOR);

        return str_replace(DIRECTORY_SEPARATOR, '/', $relative);
    }
}
