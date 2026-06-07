<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$forbidden = [
    'App\\Accessing\\',
    'AccessAccountAdministrationActionCatalogInterface',
    'AccessAccountAdministrationBridgeInterface',
    'AccessAccountAdministrationRequest',
    'AccessAccountAdministrationResult',
    'AccessAccountEntity',
];
$allowedFiles = [];
$violations = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/src', FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $file) {
    if (!$file instanceof SplFileInfo || 'php' !== $file->getExtension()) {
        continue;
    }
    $path = $file->getPathname();
    $relative = str_replace($root.DIRECTORY_SEPARATOR, '', $path);
    if (in_array(str_replace('\\', '/', $relative), $allowedFiles, true)) {
        continue;
    }
    $content = file_get_contents($path);
    if (!is_string($content)) {
        continue;
    }
    foreach ($forbidden as $needle) {
        if (str_contains($content, $needle)) {
            $violations[] = $relative.' contains '.$needle;
        }
    }
}

if ([] !== $violations) {
    fwrite(STDERR, "Administering Accessing surface cleanup guard failed:\n");
    foreach ($violations as $violation) {
        fwrite(STDERR, ' - '.$violation."\n");
    }
    exit(1);
}

echo "Administering Accessing surface cleanup guard passed.\n";
