<?php

declare(strict_types=1);

$repo = realpath($argv[1] ?? dirname(__DIR__, 2));
if (false === $repo) {
    fwrite(STDERR, "Unable to resolve repository path.\n");
    exit(2);
}

$requiredFiles = [
    'src/Command/AdministrationRuntimeScopeCapabilityIndexCommand.php',
    'src/Command/AdministrationRuntimeScopeExportCommand.php',
    'src/Command/AdministrationRuntimeScopeReferenceAuditCommand.php',
    'src/Command/AdministrationRuntimeScopeReportCommand.php',
];

$errors = [];
foreach ($requiredFiles as $relativePath) {
    $path = $repo.'/'.$relativePath;
    if (!is_file($path)) {
        $errors[] = sprintf('Missing runtime-scope output command: %s', $relativePath);
        continue;
    }

    $source = file_get_contents($path);
    if (false === $source) {
        $errors[] = sprintf('Unable to read runtime-scope output command: %s', $relativePath);
        continue;
    }

    if (!str_contains($source, 'AdministrationRuntimeScopeOutputSchemaService')) {
        $errors[] = sprintf('Runtime-scope output command must use AdministrationRuntimeScopeOutputSchemaService: %s', $relativePath);
    }

    foreach (["'componentKey'", "'inRuntimeScope'", "'composerPackageInstalled'", "'lockEnabled'", "'lockDisabled'"] as $legacyKey) {
        if (str_contains($source, $legacyKey)) {
            $errors[] = sprintf('Runtime-scope output command still emits legacy component key %s: %s', $legacyKey, $relativePath);
        }
    }
}

$decisionPath = $repo.'/src/Value/RuntimeScope/AdministrationRuntimeScopeDecision.php';
$schemaServicePath = $repo.'/src/Service/RuntimeScope/AdministrationRuntimeScopeOutputSchemaService.php';

foreach ([$decisionPath, $schemaServicePath] as $path) {
    if (!is_file($path)) {
        $errors[] = sprintf('Missing runtime-scope output schema file: %s', str_replace($repo.'/', '', $path));
    }
}

if (is_file($decisionPath)) {
    $decisionSource = (string) file_get_contents($decisionPath);
    if (!str_contains($decisionSource, "'schema' => 'administering.runtime_scope.output.v1'")) {
        $errors[] = 'AdministrationRuntimeScopeDecision::toArray() must expose administering.runtime_scope.output.v1 schema.';
    }
}

if (is_file($schemaServicePath)) {
    $schemaSource = (string) file_get_contents($schemaServicePath);
    foreach (['decisionPayload', 'exportPayload', 'administering.runtime_scope.output.v1'] as $requiredToken) {
        if (!str_contains($schemaSource, $requiredToken)) {
            $errors[] = sprintf('AdministrationRuntimeScopeOutputSchemaService is missing required token: %s', $requiredToken);
        }
    }
}

if ([] !== $errors) {
    fwrite(STDERR, "Runtime-scope output schema guard failed:\n");
    foreach ($errors as $error) {
        fwrite(STDERR, ' - '.$error.PHP_EOL);
    }
    exit(1);
}

fwrite(STDOUT, "Runtime-scope output schema guard passed.\n");
exit(0);
