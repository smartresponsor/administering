<?php

declare(strict_types=1);

$root = realpath($argv[1] ?? getcwd());
if (false === $root) {
    fwrite(STDERR, "Invalid root path.\n");
    exit(2);
}

$composerPath = $root.'/composer.json';
if (!is_file($composerPath)) {
    fwrite(STDERR, "composer.json is missing.\n");
    exit(2);
}

try {
    $composer = json_decode((string) file_get_contents($composerPath), true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $exception) {
    fwrite(STDERR, 'composer.json is invalid JSON: '.$exception->getMessage()."\n");
    exit(2);
}

$catalogPath = $root.'/config/runtime-scope/bundle_catalog.php';
$runtimePackages = [];
$allowedLocalRepositories = [
    'cruding/crud' => '../Cruding',
    'viewing/view' => '../Viewing',
    'interfacing/interface' => '../Interfacing',
    'objecting/object' => '../Objecting',
    'failing/failure' => '../Failing',
    'gating/gate' => '../Gating',
    'collectioning/collection' => '../Collectioning',
    'tabling/table' => '../Tabling',
];
if (is_file($catalogPath)) {
    $catalog = require $catalogPath;
    if (is_array($catalog) && isset($catalog['components']) && is_array($catalog['components'])) {
        foreach ($catalog['components'] as $component => $definition) {
            if ('administering' === $component || !is_array($definition)) {
                continue;
            }

            $package = $definition['package'] ?? null;
            if (is_string($package) && '' !== $package) {
                $runtimePackages[$package] = $component;
            }
        }
    }
}

$findings = [];
foreach (['require', 'require-dev'] as $section) {
    $packages = $composer[$section] ?? [];
    if (!is_array($packages)) {
        continue;
    }

    foreach (array_keys($packages) as $package) {
        if (isset($runtimePackages[$package]) && !array_key_exists($package, $allowedLocalRepositories)) {
            $findings[] = sprintf('composer.json %s requires non-baseline runtime-scope package %s for component %s; keep optional runtime-scope packages as inventory evidence.', $section, $package, $runtimePackages[$package]);
        }
    }
}

$repositories = $composer['repositories'] ?? [];
if (is_array($repositories)) {
    foreach ($repositories as $index => $repository) {
        if (!is_array($repository)) {
            continue;
        }

        $url = $repository['url'] ?? null;
        if (!is_string($url)) {
            continue;
        }

        $normalizedUrl = str_replace('\\', '/', $url);
        if (in_array($normalizedUrl, array_values($allowedLocalRepositories), true)) {
            if ('path' !== ($repository['type'] ?? null) || true !== ($repository['options']['symlink'] ?? null)) {
                $findings[] = sprintf('composer.json repositories[%d] must expose canon-permitted local sibling %s as a path repository with options.symlink=true.', $index, $url);
            }

            continue;
        }

        if (preg_match('#(^|/)\.\./[A-Z][A-Za-z0-9_-]*$#', $normalizedUrl)) {
            $findings[] = sprintf('composer.json repositories[%d] points at non-canonical sibling component path %s; optional runtime-scope packages must remain inventory evidence.', $index, $url);
        }
    }
}

if ([] !== $findings) {
    fwrite(STDERR, "Composer package boundary guard failed:\n");
    foreach ($findings as $finding) {
        fwrite(STDERR, ' - '.$finding."\n");
    }
    exit(1);
}

fwrite(STDOUT, "Composer package boundary guard passed.\n");
exit(0);
