<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$paths = [
    $root.'/config/packages/doctrine.yaml',
    $root.'/config/packages/dev/doctrine.yaml',
    $root.'/config/packages/prod/doctrine.yaml',
    $root.'/config/packages/test/doctrine.yaml',
    $root.'/config/component/doctrine.system.yaml',
];

$violations = [];

foreach ($paths as $path) {
    if (!is_file($path)) {
        continue;
    }

    $content = file_get_contents($path);
    if ($content === false) {
        $violations[] = sprintf('Cannot read %s.', $path);
        continue;
    }

    if (str_contains($content, 'auto_generate_proxy_classes')) {
        $violations[] = sprintf('Doctrine config contains removed/unsupported auto_generate_proxy_classes option: %s.', str_replace($root.'/', '', $path));
    }
}

if ($violations !== []) {
    fwrite(STDERR, implode(PHP_EOL, $violations).PHP_EOL);
    exit(1);
}

fwrite(STDOUT, 'Administering Doctrine Symfony 8.1 config guard passed.'.PHP_EOL);
