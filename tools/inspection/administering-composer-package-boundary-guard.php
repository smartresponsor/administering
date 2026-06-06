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
        if (isset($runtimePackages[$package])) {
            $findings[] = sprintf('composer.json %s requires runtime-scope package %s for component %s; keep it as composer inventory evidence, not Administering dependency.', $section, $package, $runtimePackages[$package]);
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
        if (preg_match('#(^|/)\.\./[A-Z][A-Za-z0-9_-]*$#', $normalizedUrl)) {
            $findings[] = sprintf('composer.json repositories[%d] points at sibling component path %s; Administering must not hard-wire sibling repositories.', $index, $url);
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
