<?php

declare(strict_types=1);

namespace App\Administering\Service\Config;

use Symfony\Component\Yaml\Yaml;

final readonly class ConfigFileWriterService
{
    public function __construct(private string $projectDir)
    {
    }

    /**
     * @param list<string>         $allowedRelativeFiles
     * @param array<string, mixed> $patch
     *
     * @return array{status:string, path:string, backup_path:?string, message:string}
     */
    public function write(string $rootPath, string $relativePath, array $patch, array $allowedRelativeFiles): array
    {
        if (!in_array($relativePath, $allowedRelativeFiles, true)) {
            return ['status' => 'failed', 'path' => $relativePath, 'backup_path' => null, 'message' => 'Path is not whitelisted for configuration writes.'];
        }

        $absolutePath = rtrim($rootPath, '/\\').'/'.$relativePath;
        if (!is_file($absolutePath)) {
            return ['status' => 'failed', 'path' => $relativePath, 'backup_path' => null, 'message' => 'Configuration file does not exist.'];
        }

        $current = Yaml::parseFile($absolutePath);
        if (!is_array($current)) {
            $current = [];
        }

        $merged = $this->deepMerge($current, $patch);
        $rendered = Yaml::dump($merged, 6, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK | Yaml::DUMP_NULL_AS_TILDE);

        $directory = dirname($absolutePath);
        $backupPath = $directory.'/'.basename($absolutePath).'.bak';
        $tmpPath = tempnam($directory, basename($absolutePath).'.tmp.');
        if (false === $tmpPath) {
            return ['status' => 'failed', 'path' => $relativePath, 'backup_path' => null, 'message' => 'Unable to create temporary file.'];
        }

        file_put_contents($backupPath, (string) file_get_contents($absolutePath));
        file_put_contents($tmpPath, $rendered);

        if (!is_array(Yaml::parseFile($tmpPath))) {
            @unlink($tmpPath);

            return ['status' => 'failed', 'path' => $relativePath, 'backup_path' => $backupPath, 'message' => 'Generated YAML is invalid.'];
        }

        if (!rename($tmpPath, $absolutePath)) {
            @unlink($tmpPath);

            return ['status' => 'failed', 'path' => $relativePath, 'backup_path' => $backupPath, 'message' => 'Unable to replace the target configuration file atomically.'];
        }

        return ['status' => 'applied', 'path' => $relativePath, 'backup_path' => $backupPath, 'message' => 'Configuration file updated.'];
    }

    /**
     * @param array<string, mixed> $left
     * @param array<string, mixed> $right
     *
     * @return array<string, mixed>
     */
    private function deepMerge(array $left, array $right): array
    {
        foreach ($right as $key => $value) {
            if (is_array($value) && is_array($left[$key] ?? null)) {
                $left[$key] = $this->deepMerge($left[$key], $value);
                continue;
            }

            $left[$key] = $value;
        }

        return $left;
    }
}
