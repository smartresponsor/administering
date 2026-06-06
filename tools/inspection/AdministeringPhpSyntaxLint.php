<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$paths = ['src', 'tests', 'config', 'bin', 'tools'];
$failures = [];

foreach ($paths as $relativePath) {
    $path = $root.'/'.$relativePath;
    if (!file_exists($path)) {
        continue;
    }

    if (is_file($path)) {
        $files = [$path];
    } else {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
    }

    foreach ($files as $file) {
        if (str_contains($file, DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR)) {
            continue;
        }

        $output = [];
        $exitCode = 0;
        exec('php -l '.escapeshellarg($file).' 2>&1', $output, $exitCode);
        if ($exitCode !== 0) {
            $failures[] = str_replace($root.DIRECTORY_SEPARATOR, '', $file).': '.implode(' ', $output);
        }
    }
}

if ([] !== $failures) {
    echo "PHP syntax lint failed:\n";
    foreach ($failures as $failure) {
        echo ' - '.$failure."\n";
    }
    exit(1);
}

echo "PHP syntax lint passed.\n";
