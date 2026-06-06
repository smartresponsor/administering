<?php

declare(strict_types=1);

$root = realpath(__DIR__.'/../..');
if (false === $root) {
    fwrite(STDERR, "Unable to resolve repository root.\n");
    exit(1);
}

$scopes = [
    $root.'/src/Command',
    $root.'/src/Controller',
    $root.'/src/Provider',
    $root.'/src/Service/Connected',
];

$forbidden = [
    'AdministrationRuntimeScopeStateReader' => 'Use AdministrationRuntimeScopeDecisionService outside RuntimeScope internals.',
    'AdministrationRuntimeComponentStatusService' => 'Use AdministrationRuntimeScopeDecisionService outside RuntimeScope internals.',
];

$errors = [];
foreach ($scopes as $scope) {
    if (!is_dir($scope)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($scope, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (!$file instanceof SplFileInfo || 'php' !== $file->getExtension()) {
            continue;
        }

        $path = $file->getPathname();
        $contents = (string) file_get_contents($path);
        foreach ($forbidden as $needle => $message) {
            if (!str_contains($contents, $needle)) {
                continue;
            }

            $relativePath = str_replace($root.'/', '', str_replace('\\', '/', $path));
            $errors[] = sprintf('%s: %s (%s)', $relativePath, $needle, $message);
        }
    }
}

if ([] !== $errors) {
    fwrite(STDERR, "Runtime-scope decision boundary guard failed:\n");
    foreach ($errors as $error) {
        fwrite(STDERR, ' - '.$error."\n");
    }
    exit(1);
}

echo "Runtime-scope decision boundary guard passed.\n";
