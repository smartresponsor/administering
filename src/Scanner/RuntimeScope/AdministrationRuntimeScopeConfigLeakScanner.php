<?php

declare(strict_types=1);

namespace App\Administering\Scanner\RuntimeScope;

final class AdministrationRuntimeScopeConfigLeakScanner
{
    /**
     * @param list<string> $disabledComponents
     *
     * @return list<array{file: string, line: int, component: string, pattern: string, excerpt: string}>
     */
    public function scan(string $hostDir, array $disabledComponents): array
    {
        $normalizedComponents = [];
        foreach ($disabledComponents as $component) {
            if ('' === trim($component)) {
                continue;
            }
            $normalizedComponents[] = strtolower(trim($component));
        }
        $normalizedComponents = array_values(array_unique($normalizedComponents));

        if ([] === $normalizedComponents) {
            return [];
        }

        $scanRoots = [
            'config',
            '.env',
            '.env.local',
            '.env.prod',
            '.env.test',
        ];

        $findings = [];
        foreach ($this->iterFiles($hostDir, $scanRoots) as $filePath) {
            $relativePath = $this->relativePath($hostDir, $filePath);
            $lines = @file($filePath, FILE_IGNORE_NEW_LINES);
            if (!is_array($lines)) {
                continue;
            }

            foreach ($lines as $index => $line) {
                foreach ($normalizedComponents as $component) {
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

    /**
     * @param list<string> $scanRoots
     *
     * @return iterable<string>
     */
    private function iterFiles(string $hostDir, array $scanRoots): iterable
    {
        $hostDir = rtrim($hostDir, '/\\');
        foreach ($scanRoots as $scanRoot) {
            $path = $hostDir.'/'.$scanRoot;
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
                if (!in_array($extension, ['yaml', 'yml', 'php', 'env', 'xml'], true)) {
                    continue;
                }

                yield $file->getPathname();
            }
        }
    }

    private function relativePath(string $hostDir, string $path): string
    {
        $root = rtrim(str_replace('\\', '/', $hostDir), '/').'/';
        $path = str_replace('\\', '/', $path);

        return str_starts_with($path, $root) ? substr($path, strlen($root)) : $path;
    }
}
