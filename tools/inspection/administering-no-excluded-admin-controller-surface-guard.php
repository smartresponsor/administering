<?php

declare(strict_types=1);

$repo = $argv[1] ?? dirname(__DIR__, 2);
$repo = rtrim((string) realpath($repo), DIRECTORY_SEPARATOR);
if ('' === $repo) {
    fwrite(STDERR, "Unable to resolve repository path.\n");
    exit(2);
}

$surfaceParts = ['src', 'Controller', 'Admin', 'Surface'];
$surfaceDir = $repo.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $surfaceParts);
$markers = [
    implode('/', $surfaceParts),
    implode('/', array_slice($surfaceParts, 1)),
    implode('\\\\', array_slice($surfaceParts, 1)),
    'namespace App'.'\\\\'.'Administering'.'\\\\'.implode('\\\\', array_slice($surfaceParts, 1)),
];

$violations = [];
if (is_dir($surfaceDir)) {
    $violations[] = implode('/', $surfaceParts).' directory must not exist.';
}

$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($repo, FilesystemIterator::SKIP_DOTS));
foreach ($files as $file) {
    if (!$file instanceof SplFileInfo || !$file->isFile()) {
        continue;
    }

    $path = $file->getPathname();
    $relative = str_replace('\\', '/', substr($path, strlen($repo) + 1));
    if ($relative === 'tools/inspection/administering-no-excluded-admin-controller-surface-guard.php') {
        continue;
    }
    if (str_starts_with($relative, 'vendor/') || str_starts_with($relative, 'var/') || str_starts_with($relative, 'tools/inspection/')) {
        continue;
    }

    if (!preg_match('/\.(php|yaml|yml|twig|xml|neon|dist|ps1)$/', $relative)) {
        continue;
    }

    $contents = (string) file_get_contents($path);
    foreach ($markers as $needle) {
        if (str_contains($contents, $needle)) {
            $violations[] = $relative.' contains forbidden excluded admin controller surface marker.';
        }
    }
}

if ([] !== $violations) {
    foreach ($violations as $violation) {
        fwrite(STDERR, $violation."\n");
    }
    exit(1);
}

echo "No excluded admin controller surface guard passed.\n";
