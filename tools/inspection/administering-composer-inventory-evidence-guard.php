<?php

declare(strict_types=1);

$root = realpath($argv[1] ?? getcwd());
if (false === $root) {
    fwrite(STDERR, "Invalid root path.\n");
    exit(2);
}

$findings = [];
$stateReader = $root.'/src/Reader/RuntimeScope/AdministrationRuntimeScopeStateReader.php';
if (is_file($stateReader)) {
    $content = (string) file_get_contents($stateReader);
    foreach (['NON_COMPONENT_PACKAGE_PREFIXES', 'componentsFromComposerPackages', 'looksLikeComponentPackage', 'normalizeComponent(string $component)'] as $forbidden) {
        if (str_contains($content, $forbidden)) {
            $findings[] = sprintf('StateReader contains composer package heuristic %s; runtime-scope components must come from token catalog evidence.', $forbidden);
        }
    }
}

$statusService = $root.'/src/Service/RuntimeScope/AdministrationRuntimeComponentStatusService.php';
if (is_file($statusService)) {
    $content = (string) file_get_contents($statusService);
    foreach (['componentFromPackage', 'explode(\'/\', $package', 'smart-responsor', 'smartresponsor'] as $forbidden) {
        if (str_contains($content, $forbidden)) {
            $findings[] = sprintf('ComponentStatusService contains package-to-component heuristic %s; use composerComponentPackages evidence from state.', $forbidden);
        }
    }
}

$inventoryReader = $root.'/src/Reader/RuntimeScope/AdministrationRuntimeScopeComposerInventoryReader.php';
if (!is_file($inventoryReader)) {
    $findings[] = 'Runtime-scope composer inventory reader is missing.';
} else {
    $content = (string) file_get_contents($inventoryReader);
    if (!str_contains($content, 'AdministrationRuntimeScopeComposerInventoryEvidence')) {
        $findings[] = 'ComposerInventoryReader must return AdministrationRuntimeScopeComposerInventoryEvidence for component mapping.';
    }
    if (!str_contains($content, '$catalog')) {
        $findings[] = 'ComposerInventoryReader must map component packages through Administering token catalog evidence.';
    }
}

if ([] !== $findings) {
    fwrite(STDERR, "Composer inventory evidence guard failed:\n");
    foreach ($findings as $finding) {
        fwrite(STDERR, ' - '.$finding."\n");
    }
    exit(1);
}

fwrite(STDOUT, "Composer inventory evidence guard passed.\n");
exit(0);
