<?php

declare(strict_types=1);

namespace App\Administering\Scanner\RuntimeScope;

final class AdministrationRuntimeScopeReferenceScanner
{
    /**
     * @param list<string> $forbiddenComponents
     *
     * @return list<array{file: string, line: int, component: string, pattern: string, excerpt: string}>
     */
    public function scan(string $hostDir, array $forbiddenComponents): array
    {
        $components = [];
        foreach ($forbiddenComponents as $component) {
            if ('' !== trim($component)) {
                $components[] = strtolower(trim($component));
            }
        }
        $components = array_values(array_unique($components));

        if ([] === $components) {
            return [];
        }

        $findings = [];
        foreach ($this->iterFiles($hostDir) as $filePath) {
            $relativePath = $this->relativePath($hostDir, $filePath);
            $lines = @file($filePath, FILE_IGNORE_NEW_LINES);
            if (!is_array($lines)) {
                continue;
            }

            foreach ($lines as $index => $line) {
                foreach ($components as $component) {
                    foreach ($this->patternsFor($component) as $pattern) {
                        if (false === stripos($line, $pattern)) {
                            continue;
                        }

                        $findings[] = [
                            'file' => $relativePath,
                            'line' => $index + 1,
                            'component' => $component,
                            'pattern' => $pattern,
                            'excerpt' => trim($line),
                        ];
                    }
                }
            }
        }

        return $findings;
    }

    /** @return iterable<string> */
    private function iterFiles(string $hostDir): iterable
    {
        $hostDir = rtrim($hostDir, '/\\');
        $roots = [
            'src',
            'config',
            'templates',
            'migrations',
            '.env',
            '.env.local',
            '.env.prod',
            '.env.test',
        ];

        foreach ($roots as $root) {
            $path = $hostDir.'/'.$root;
            if (is_file($path)) {
                yield $path;
                continue;
            }

            if (!is_dir($path)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            );

            foreach ($iterator as $file) {
                if (!$file instanceof \SplFileInfo || !$file->isFile()) {
                    continue;
                }

                $extension = strtolower($file->getExtension());
                if (!in_array($extension, ['php', 'yaml', 'yml', 'xml', 'twig', 'env'], true)) {
                    continue;
                }

                yield $file->getPathname();
            }
        }
    }

    /** @return list<string> */
    private function patternsFor(string $component): array
    {
        $studly = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $component)));

        return array_values(array_unique([
            $component,
            'routes/'.$component,
            'services/'.$component,
            'packages/'.$component,
            'templates/'.$component,
            'vendor/'.$component,
            '@'.$studly.'Bundle',
            $studly.'Bundle',
            'App\\'.$studly.'\\',
            'App\\\\'.$studly.'\\\\',
        ]));
    }

    private function relativePath(string $hostDir, string $path): string
    {
        $root = rtrim(str_replace('\\', '/', $hostDir), '/').'/';
        $path = str_replace('\\', '/', $path);

        return str_starts_with($path, $root) ? substr($path, strlen($root)) : $path;
    }
}
