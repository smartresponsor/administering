<?php

declare(strict_types=1);

$root = realpath($argv[1] ?? getcwd());
if (false === $root) {
    fwrite(STDERR, "Invalid root path.\n");
    exit(2);
}

$scanRoots = ['src', 'config', 'tests', 'templates'];
$ownerNamespaces = [
    'Accessing', 'Adjudicating', 'Analysing', 'Applicating', 'Attaching',
    'Billing', 'Carting', 'Cataloging', 'Commercializing', 'Commissioning',
    'Configuring', 'Cruding', 'Currencing', 'Discovering', 'Domaining',
    'Exchanging', 'Faceting', 'Facting', 'Indexing', 'Interfacing',
    'Localizing', 'Locating', 'Managing', 'Merchandising', 'Messaging',
    'Navigating', 'Objecting', 'Observabiliting', 'Ordering', 'Paging',
    'Paying', 'Projecting', 'Rolling', 'Searching', 'Shipping',
    'Subscripting', 'Tagging', 'Taxating', 'Vendoring', 'Viewing',
];

$forbidden = [];
foreach ($ownerNamespaces as $ownerNamespace) {
    $forbidden[] = 'use App\\'.$ownerNamespace.'\\';
    $forbidden[] = 'extends App\\'.$ownerNamespace.'\\';
    $forbidden[] = 'implements App\\'.$ownerNamespace.'\\';
    $forbidden[] = 'new App\\'.$ownerNamespace.'\\';
    $forbidden[] = 'App\\'.$ownerNamespace.'\\';
}

$findings = [];
foreach ($scanRoots as $scanRoot) {
    $directory = $root.DIRECTORY_SEPARATOR.$scanRoot;
    if (!is_dir($directory)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (!$file instanceof SplFileInfo || !$file->isFile() || 'php' !== strtolower($file->getExtension())) {
            continue;
        }

        $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
        $lines = file($file->getPathname(), FILE_IGNORE_NEW_LINES) ?: [];
        foreach ($lines as $lineNumber => $line) {
            foreach ($forbidden as $pattern) {
                if (str_contains($line, $pattern)) {
                    $findings[] = sprintf('%s:%d contains forbidden owner-component PHP coupling pattern %s', $relativePath, $lineNumber + 1, $pattern);
                }
            }
        }
    }
}

