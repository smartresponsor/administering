<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$menuFile = $root.'/src/Builder/Admin/AdministrationMainMenuBuilder.php';
$connectedCrudFile = $root.'/src/Controller/Admin/Crud/AdministrationConnectedComponentRecordCrudController.php';
$commandControllerFile = $root.'/src/Controller/Admin/Command/AdministrationCommandIndexController.php';
$commandIndexServiceFile = $root.'/src/Service/Admin/AdministrationCommandIndexService.php';

foreach ([$menuFile, $connectedCrudFile, $commandControllerFile, $commandIndexServiceFile] as $file) {
    if (!is_file($file)) {
        fwrite(STDERR, sprintf("Required operator-index file is missing: %s\n", $file));
        exit(1);
    }
}

$menu = (string) file_get_contents($menuFile);
foreach (['Home', 'Tools', 'Commands', 'Enabled Components'] as $label) {
    if (!str_contains($menu, $label)) {
        fwrite(STDERR, sprintf("Primary menu label is missing: %s\n", $label));
        exit(1);
    }
}

foreach (['Configuration Center', 'Runtime Scope', 'Connected Components'] as $legacyLabel) {
    if (str_contains($menu, $legacyLabel)) {
        fwrite(STDERR, sprintf("Legacy primary menu label must not remain: %s\n", $legacyLabel));
        exit(1);
    }
}

$connectedCrud = (string) file_get_contents($connectedCrudFile);
foreach (['Enabled Components', 'In APP_RUNTIME_SCOPE', 'Enabled now', 'Disabled by lock'] as $token) {
    if (!str_contains($connectedCrud, $token)) {
        fwrite(STDERR, sprintf("Enabled Components CRUD token is missing: %s\n", $token));
        exit(1);
    }
}

$commandController = (string) file_get_contents($commandControllerFile);
if (!str_contains($commandController, "name: 'administration_command_index'")) {
    fwrite(STDERR, "Command index route is missing.\n");
    exit(1);
}

$commandIndexService = (string) file_get_contents($commandIndexServiceFile);
if (!str_contains($commandIndexService, 'src/Command/*.php')) {
    fwrite(STDERR, "Command index service must scan src/Command/*.php.\n");
    exit(1);
}

fwrite(STDOUT, "Administering operator index menu guard passed.\n");
