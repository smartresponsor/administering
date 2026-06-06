<?php

declare(strict_types=1);

$repo = $argv[1] ?? dirname(__DIR__, 2);
$repo = realpath((string) $repo);
if (false === $repo) {
    fwrite(STDERR, "Unable to resolve repository path.\n");
    exit(2);
}

$violations = [];
$providerDir = $repo.'/src/Provider/Connected';
if (is_dir($providerDir)) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($providerDir, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (!$file instanceof SplFileInfo || 'php' !== $file->getExtension()) {
            continue;
        }

        $path = $file->getPathname();
        $contents = (string) file_get_contents($path);
        if (str_contains($contents, '->componentRows()')) {
            $violations[] = $path.' uses array componentRows() instead of normalized decisionRows().';
        }
    }
}

$requiredFiles = [
    $repo.'/src/Value/RuntimeScope/AdministrationRuntimeScopeDecisionRow.php',
    $repo.'/src/Value/RuntimeScope/AdministrationRuntimeScopeDecision.php',
    $repo.'/src/Service/Connected/AdministrationRuntimeScopeConnectedComponentProjectionService.php',
];
foreach ($requiredFiles as $requiredFile) {
    if (!is_file($requiredFile)) {
        $violations[] = $requiredFile.' is missing.';
    }
}

$decision = is_file($repo.'/src/Value/RuntimeScope/AdministrationRuntimeScopeDecision.php')
    ? (string) file_get_contents($repo.'/src/Value/RuntimeScope/AdministrationRuntimeScopeDecision.php')
    : '';
foreach (['decisionRows', 'decisionRowsByComponent', 'componentRows'] as $method) {
    if (!str_contains($decision, 'function '.$method.'(')) {
        $violations[] = 'AdministrationRuntimeScopeDecision must expose '.$method.'().';
    }
}

$projection = is_file($repo.'/src/Service/Connected/AdministrationRuntimeScopeConnectedComponentProjectionService.php')
    ? (string) file_get_contents($repo.'/src/Service/Connected/AdministrationRuntimeScopeConnectedComponentProjectionService.php')
    : '';
foreach (['decisionRows', 'decisionRowsByComponent'] as $method) {
    if (!str_contains($projection, 'function '.$method.'(')) {
        $violations[] = 'Connected projection must expose '.$method.'().';
    }
}

if ([] !== $violations) {
    fwrite(STDERR, "Runtime decision row surface guard failed:\n");
    foreach ($violations as $violation) {
        fwrite(STDERR, ' - '.$violation.PHP_EOL);
    }
    exit(1);
}

fwrite(STDOUT, "Runtime decision row surface guard passed.\n");
exit(0);
