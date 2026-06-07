<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$command = $root.'/src/Command/AdministrationRuntimeReadinessCommand.php';

if (!is_file($command)) {
    fwrite(STDERR, "Missing AdministrationRuntimeReadinessCommand.php\n");
    exit(1);
}

$contents = file_get_contents($command);
if (false === $contents) {
    fwrite(STDERR, "Unable to read AdministrationRuntimeReadinessCommand.php\n");
    exit(1);
}

$forbidden = [
    'App\\Rolling\\',
    'RollingAdministrationPermissionCatalogInterface',
    'RollingAdministrationPermissionDecisionServiceInterface',
    'rollingPermissionCatalog',
    'rollingPermissionDecisionService',
];

foreach ($forbidden as $needle) {
    if (str_contains($contents, $needle)) {
        fwrite(STDERR, sprintf("Forbidden optional Rolling dependency in runtime readiness command: %s\n", $needle));
        exit(1);
    }
}

if (!str_contains($contents, 'private function localPermissionCatalog(): array')) {
    fwrite(STDERR, "Runtime readiness command must use an Administering-owned local permission catalog.\n");
    exit(1);
}

fwrite(STDOUT, "Administering runtime readiness Rolling cleanup guard passed.\n");
