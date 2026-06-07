<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$controller = $root.'/src/Controller/Admin/Surface/AdministrationPermissionCatalogController.php';

$failures = [];
if (!is_file($controller)) {
    $failures[] = 'Missing AdministrationPermissionCatalogController.';
} else {
    $contents = file_get_contents($controller);
    if (false === $contents) {
        $failures[] = 'Unable to read AdministrationPermissionCatalogController.';
    } else {
        foreach ([
            'App\\Rolling\\',
            'RollingAdministrationPermissionCatalogInterface',
            '$permissionCatalog',
        ] as $forbidden) {
            if (str_contains($contents, $forbidden)) {
                $failures[] = sprintf('AdministrationPermissionCatalogController must not contain optional Rolling dependency "%s".', $forbidden);
            }
        }

        foreach ([
            'private const PERMISSION_DESCRIPTORS',
            'administration.rolling.permission_catalog.view',
            "'descriptors' => self::PERMISSION_DESCRIPTORS",
        ] as $required) {
            if (!str_contains($contents, $required)) {
                $failures[] = sprintf('AdministrationPermissionCatalogController is missing required local catalog marker "%s".', $required);
            }
        }
    }
}

if ([] !== $failures) {
    fwrite(STDERR, "Administering permission catalog surface cleanup guard failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, ' - '.$failure."\n");
    }
    exit(1);
}

echo "Administering permission catalog surface cleanup guard passed.\n";
