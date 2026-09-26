<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);

$failures = [];
$warnings = [];
$checks = [];

$add = static function (string $nameEntity, bool $passed, string $detail = '') use (&$checks, &$failures): void {
    $checks[] = [$nameEntity, $passed, $detail];
    if (!$passed) {
        $failures[] = $nameEntity.($detail !== '' ? ' - '.$detail : '');
    }
};

$warn = static function (string $nameEntity, bool $condition, string $detail = '') use (&$warnings): void {
    if ($condition) {
        $warnings[] = $nameEntity.($detail !== '' ? ' - '.$detail : '');
    }
};

$read = static fn (string $path): string => is_file($path) ? (string) file_get_contents($path) : '';

$binConsole = $root.'/bin/console';
$add('bin/console is a file', is_file($binConsole), 'bin/console must not be a directory');
$add('bin/console is executable PHP entrypoint', str_starts_with($read($binConsole), '#!/usr/bin/env php'), 'missing PHP shebang');
$add('standalone Kernel exists', is_file($root.'/src/Kernel.php'));
$add('standalone bundles map exists', is_file($root.'/config/bundles.php'));
$add('standalone framework config exists', is_file($root.'/config/packages/framework.yaml'));
$add('standalone doctrine config exists', is_file($root.'/config/packages/doctrine.yaml'));
$add('standalone route import exists', is_file($root.'/config/routes/administration_standalone.yaml'));

$composerPath = $root.'/composer.json';
$composer = json_decode($read($composerPath), true);
$add('composer.json is valid JSON', is_array($composer), json_last_error_msg());
if (is_array($composer)) {
    $scripts = $composer['scripts'] ?? [];
    $add('composer script inspect:standalone exists', isset($scripts['inspect:standalone']));
    $add('composer script lint:php exists', isset($scripts['lint:php']));
    $add('composer script quality:local exists', isset($scripts['quality:local']));
    $consoleScript = $scripts['console'] ?? null;
    $add(
        'composer script console targets bin/console',
        is_string($consoleScript) && in_array(trim($consoleScript), ['bin/console', 'php bin/console', '@php bin/console'], true),
        'expected bin/console, php bin/console, or @php bin/console',
    );
}

$services = $read($root.'/config/services.yaml');
$add(
    'component integration contracts are tagged',
    str_contains($services, 'App\\Administering\\Contract\\AdministrationComponentIntegrationContractInterface')
    && str_contains($services, 'administering.component_integration_contract')
);

foreach (['.php-cs-fixer.cache', '.phpunit.result.cache'] as $cacheFile) {
    $trackedOutput = [];
    $ignoredOutput = [];
    exec(sprintf('git -C %s ls-files --error-unmatch -- %s 2>NUL', escapeshellarg($root), escapeshellarg($cacheFile)), $trackedOutput, $trackedExitCode);
    exec(sprintf('git -C %s check-ignore -q -- %s', escapeshellarg($root), escapeshellarg($cacheFile)), $ignoredOutput, $ignoredExitCode);
    $add(
        $cacheFile.' is not committed source',
        0 !== $trackedExitCode && 0 === $ignoredExitCode,
        'generated local cache may exist, but it must be untracked and ignored',
    );
}

$phpFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
$declaredClasses = [];
foreach ($phpFiles as $file) {
    if (!$file instanceof SplFileInfo || $file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $relativePath = str_replace('\\', '/', ltrim(str_replace($root, '', $path), DIRECTORY_SEPARATOR));
    if (
        str_starts_with($relativePath, 'vendor/')
        || str_starts_with($relativePath, 'var/')
        || str_starts_with($relativePath, '.gating/')
        || str_starts_with($relativePath, 'tools/stubs/payload/')
        || str_starts_with($relativePath, '.git/')
    ) {
        continue;
    }

    $content = $read($path);
    if (preg_match('/namespace\s+([^;]+);/m', $content, $namespace) && preg_match('/^(?:\s*(?:final|abstract|readonly)\s+)*(?:class|interface|trait|enum)\s+(\w+)/m', $content, $symbol)) {
        $fqcn = trim($namespace[1]).'\\'.$symbol[1];
        $declaredClasses[$fqcn][] = $relativePath;
    }
}
$duplicates = [];
foreach ($declaredClasses as $fqcn => $paths) {
    if (count($paths) > 1) {
        $duplicates[] = $fqcn.' => '.implode(', ', $paths);
    }
}
$add('no duplicate declared PHP symbols', [] === $duplicates, implode('; ', array_slice($duplicates, 0, 5)));

$warn('legacy interface mirror directories still exist', (bool) glob($root.'/src/*Interface', GLOB_ONLYDIR), 'cleanup should follow after standalone hardening');
$warn('stale Config tests may still exist', (bool) glob($root.'/tests/Unit/Config/*Config*Test.php'), 'remove or rewrite in next cleanup wave');

$failed = [] !== $failures;
echo "Administering standalone readiness report\n";
echo "========================================\n";
foreach ($checks as [$nameEntity, $passed, $detail]) {
    echo ($passed ? '[OK]   ' : '[FAIL] ').$nameEntity;
    if (!$passed && $detail !== '') {
        echo ' :: '.$detail;
    }
    echo "\n";
}

if ([] !== $warnings) {
    echo "\nWarnings\n--------\n";
    foreach ($warnings as $warning) {
        echo '[WARN] '.$warning."\n";
    }
}

echo "\nResult: ".($failed ? 'FAILED' : 'PASSED')."\n";
exit($failed ? 1 : 0);
