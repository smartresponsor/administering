<?php

declare(strict_types=1);

$repo = $argv[1] ?? dirname(__DIR__, 2);
$repo = realpath((string) $repo);
if (false === $repo) {
    fwrite(STDERR, "Unable to resolve repository path.\n");
    exit(2);
}

$php = PHP_BINARY;
$checks = [
    [
        'name' => 'zero foreign coupling',
        'script' => __DIR__.'/administering-zero-foreign-coupling-guard.php',
        'args' => [$repo],
    ],
    [
        'name' => 'runtime-scope decision boundary',
        'script' => __DIR__.'/administering-runtime-scope-decision-boundary-guard.php',
        'args' => [],
    ],
    [
        'name' => 'no excluded admin controller surface',
        'script' => __DIR__.'/administering-no-excluded-admin-controller-surface-guard.php',
        'args' => [$repo],
    ],
    [
        'name' => 'composer package boundary',
        'script' => __DIR__.'/administering-composer-package-boundary-guard.php',
        'args' => [$repo],
    ],
    [
        'name' => 'composer inventory evidence',
        'script' => __DIR__.'/administering-composer-inventory-evidence-guard.php',
        'args' => [$repo],
    ],
    [
        'name' => 'runtime decision row surface',
        'script' => __DIR__.'/administering-runtime-decision-row-surface-guard.php',
        'args' => [$repo],
    ],
    [
        'name' => 'runtime output schema',
        'script' => __DIR__.'/administering-runtime-output-schema-guard.php',
        'args' => [$repo],
    ],
];

$failed = [];
foreach ($checks as $check) {
    $command = array_merge([$php, $check['script']], $check['args']);
    $escaped = array_map(static fn (string $part): string => escapeshellarg($part), $command);
    $output = [];
    $exitCode = 0;

    exec(implode(' ', $escaped).' 2>&1', $output, $exitCode);

    if (0 !== $exitCode) {
        $failed[] = [
            'name' => $check['name'],
            'exitCode' => $exitCode,
            'output' => $output,
        ];
        continue;
    }

    fwrite(STDOUT, sprintf('[OK] %s%s', $check['name'], PHP_EOL));
}

if ([] !== $failed) {
    fwrite(STDERR, "Administering architecture guard suite failed:\n");
    foreach ($failed as $failure) {
        fwrite(STDERR, sprintf(' - %s exited with %d%s', $failure['name'], $failure['exitCode'], PHP_EOL));
        foreach ($failure['output'] as $line) {
            fwrite(STDERR, '   '.$line.PHP_EOL);
        }
    }
    exit(1);
}

fwrite(STDOUT, "Administering architecture guard suite passed.\n");
exit(0);
