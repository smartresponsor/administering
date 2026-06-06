<?php

declare(strict_types=1);

$root = realpath($argv[1] ?? getcwd());
if (false === $root) {
    fwrite(STDERR, "Invalid root path.\n");
    exit(2);
}

$scanRoots = [
    'src',
    'config',
    'tests',
    'templates',
];

$forbiddenPatterns = [
    'App\\Accessing\\',
    'App\\Rolling\\',
    'App\\Managing\\',
    'App\\Configuring\\',
    'App\\\\Accessing\\\\',
    'App\\\\Rolling\\\\',
    'App\\\\Managing\\\\',
    'App\\\\Configuring\\\\',
];

$findings = [];
foreach ($scanRoots as $scanRoot) {
    $directory = $root.DIRECTORY_SEPARATOR.$scanRoot;
    if (!is_dir($directory)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (!$file instanceof SplFileInfo || !$file->isFile()) {
            continue;
        }

        $path = $file->getPathname();
        $relativePath = str_replace('\\', '/', substr($path, strlen($root) + 1));
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($extension, ['php', 'yaml', 'yml', 'twig', 'xml', 'neon'], true)) {
            continue;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if (false === $lines) {
            continue;
        }

        foreach ($lines as $index => $line) {
            foreach ($forbiddenPatterns as $pattern) {
                if (str_contains($line, $pattern)) {
                    $findings[] = sprintf('%s:%d contains forbidden foreign PHP coupling pattern %s', $relativePath, $index + 1, $pattern);
                }
            }
        }
    }
}

$catalogPath = $root.'/config/runtime-scope/bundle_catalog.php';
if (is_file($catalogPath)) {
    $catalogLines = file($catalogPath, FILE_IGNORE_NEW_LINES) ?: [];
    foreach ($catalogLines as $index => $line) {
        if (str_contains($line, 'App\\') || str_contains($line, 'App\\\\')) {
            $findings[] = sprintf('config/runtime-scope/bundle_catalog.php:%d contains PHP class-shaped catalog evidence', $index + 1);
        }
        if (str_contains($line, "'bundle' =>")) {
            $findings[] = sprintf('config/runtime-scope/bundle_catalog.php:%d uses legacy bundle key; use bundle_token', $index + 1);
        }
    }
}

if ([] !== $findings) {
    fwrite(STDERR, "Zero foreign coupling guard failed:\n");
    foreach ($findings as $finding) {
        fwrite(STDERR, ' - '.$finding."\n");
    }
    exit(1);
}

fwrite(STDOUT, "Zero foreign coupling guard passed.\n");
exit(0);
