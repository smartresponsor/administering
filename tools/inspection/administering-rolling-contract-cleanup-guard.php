<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$providerPath = $root.'/src/Contract/Rolling/AdministrationRollingComponentIntegrationContractProvider.php';

if (!is_file($providerPath)) {
    fwrite(STDERR, "Missing Rolling contract provider: {$providerPath}\n");
    exit(1);
}

$contents = (string) file_get_contents($providerPath);

$forbidden = [
    'App\\Rolling\\Contract\\RollingIntegrationContract',
    'App\\Rolling\\Provider\\Configuration\\RollingConfigurationToolProvider',
    'RollingIntegrationContract',
    'RollingConfigurationToolProvider',
    'private readonly $rollingProvider',
];

foreach ($forbidden as $needle) {
    if (str_contains($contents, $needle)) {
        fwrite(STDERR, "Rolling provider still depends on optional external Rolling class: {$needle}\n");
        exit(1);
    }
}

$required = [
    'implements AdministrationComponentIntegrationContractInterface',
    'public function componentKey(): string',
    "return 'rolling';",
    'public function contract(): object',
    'new readonly class',
];

foreach ($required as $needle) {
    if (!str_contains($contents, $needle)) {
        fwrite(STDERR, "Rolling provider misses required self-contained contract marker: {$needle}\n");
        exit(1);
    }
}

$syntax = [];
$returnCode = 0;
exec('php -l '.escapeshellarg($providerPath), $syntax, $returnCode);
if ($returnCode !== 0) {
    fwrite(STDERR, implode("\n", $syntax)."\n");
    exit(1);
}

echo "Administering Rolling contract cleanup guard passed.\n";
