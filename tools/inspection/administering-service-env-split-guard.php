<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$violations = [];

$kernelFile = $root.'/src/Kernel.php';
$kernel = is_file($kernelFile) ? (string) file_get_contents($kernelFile) : '';

if (!str_contains($kernel, "../config/services.yaml")) {
    $violations[] = 'Kernel must import config/services.yaml as the base DI graph.';
}

if (!str_contains($kernel, "../config/services_'.\$this->environment.'.yaml")) {
    $violations[] = 'Kernel must import optional config/services_<env>.yaml overlay.';
}

foreach ([
    $root.'/config/services.standalone.yaml',
    $root.'/config/services.dev.yaml',
    $root.'/config/services.prod.yaml',
] as $forbiddenFile) {
    if (is_file($forbiddenFile)) {
        $violations[] = 'Forbidden service split file exists: '.substr($forbiddenFile, strlen($root) + 1);
    }
}

if (is_file($root.'/config/services_prod.yaml')) {
    $violations[] = 'config/services_prod.yaml must not exist while prod equals config/services.yaml.';
}

if (!is_file($root.'/config/services_dev.yaml')) {
    $violations[] = 'config/services_dev.yaml must exist as the dev dry-runtime overlay.';
}

$serviceFiles = glob($root.'/config/services*.yaml') ?: [];
foreach ($serviceFiles as $serviceFile) {
    $content = (string) file_get_contents($serviceFile);
    if (str_contains($content, 'when@')) {
        $violations[] = 'when@ block is forbidden in '.substr($serviceFile, strlen($root) + 1);
    }
    if (str_contains($content, '../../Administering/config/services.yaml')) {
        $violations[] = 'Self/sibling Administering services import is forbidden in '.substr($serviceFile, strlen($root) + 1);
    }
}

if ([] !== $violations) {
    fwrite(STDERR, "Administering service env split guard failed:\n");
    foreach ($violations as $violation) {
        fwrite(STDERR, '- '.$violation."\n");
    }
    exit(1);
}

echo "Administering service env split guard passed.\n";
