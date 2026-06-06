<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$provider = $root.'/src/Contract/Accessing/AdministrationAccessingComponentIntegrationContractProvider.php';
$stub = $root.'/src/Contract/Accessing/AdministrationAccessingComponentIntegrationContractStub.php';

if (!is_file($provider)) {
    fwrite(STDERR, "Missing Accessing contract provider.\n");
    exit(1);
}

if (is_file($stub)) {
    fwrite(STDERR, "Legacy Accessing contract stub must be removed to avoid duplicate component contracts.\n");
    exit(1);
}

$contents = file_get_contents($provider);
if (false === $contents) {
    fwrite(STDERR, "Unable to read Accessing contract provider.\n");
    exit(1);
}

$forbidden = [
    'App\\Accessing\\',
    'AccessIntegrationContract',
    'AccessingConfigurationToolProvider',
];

foreach ($forbidden as $needle) {
    if (str_contains($contents, $needle)) {
        fwrite(STDERR, sprintf("Accessing contract provider must not depend on optional external Accessing class: %s\n", $needle));
        exit(1);
    }
}

if (!str_contains($contents, 'public function contract(): object')) {
    fwrite(STDERR, "Accessing contract provider must return object, not an external typed contract.\n");
    exit(1);
}

fwrite(STDOUT, "Administering Accessing contract cleanup guard passed.\n");
