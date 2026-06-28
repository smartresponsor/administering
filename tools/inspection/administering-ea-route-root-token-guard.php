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

$coreEntity = $composer['extra']['core-entity'] ?? null;
if (!is_string($coreEntity) || '' === trim($coreEntity)) {
    fwrite(STDERR, "composer.json extra.core-entity is missing.\n");
    exit(2);
}

$coreRootToken = strtolower(trim($coreEntity));
if (!preg_match('/^[a-z][a-z0-9]*$/', $coreRootToken)) {
    fwrite(STDERR, sprintf('Invalid core-entity root token %s; expected a single URI token.%s', $coreRootToken, PHP_EOL));
    exit(2);
}

$productName = $composer['extra']['product-name'] ?? '';
$productRootToken = is_string($productName) ? strtolower(trim($productName)) : '';

$pathsToScan = [
    $root.'/src',
    $root.'/templates',
    $root.'/config',
];

$allowedFileExtensions = [
    'php' => true,
    'twig' => true,
    'yaml' => true,
    'yml' => true,
];

$administeringOwnedTokens = [
    'component' => true,
    'command' => true,
    'config' => true,
    'operation' => true,
    'runtime' => true,
    'section' => true,
    'tool' => true,
];

$findings = [];
$hasCoreRootRoute = false;

foreach ($pathsToScan as $pathToScan) {
    if (!is_dir($pathToScan)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pathToScan));
    foreach ($iterator as $file) {
        if (!$file instanceof SplFileInfo || !$file->isFile()) {
            continue;
        }

        $extension = strtolower($file->getExtension());
        if (!isset($allowedFileExtensions[$extension])) {
            continue;
        }

        $content = (string) file_get_contents($file->getPathname());
        if ('' === $content || !str_contains($content, '/ea')) {
            continue;
        }

        preg_match_all('#(?<![A-Za-z0-9_])/ea(?=/|[^A-Za-z0-9_])(?:/[A-Za-z0-9._{}-]+)*#', $content, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as [$uri, $offset]) {
            $segments = explode('/', trim($uri, '/'));
            $rootToken = $segments[1] ?? null;
            $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
            $line = substr_count(substr($content, 0, (int) $offset), "\n") + 1;

            if (null === $rootToken || '' === $rootToken) {
                $findings[] = sprintf('%s:%d uses %s without a component root token after /ea; expected /ea/%s.', $relativePath, $line, $uri, $coreRootToken);
                continue;
            }

            if ($rootToken === $coreRootToken) {
                $hasCoreRootRoute = true;
                continue;
            }

            if ('' !== $productRootToken && $rootToken === $productRootToken) {
                $findings[] = sprintf('%s:%d uses product-name token %s in %s; use composer extra.core-entity token %s instead.', $relativePath, $line, $rootToken, $uri, $coreRootToken);
                continue;
            }

            if (isset($administeringOwnedTokens[$rootToken])) {
                $findings[] = sprintf('%s:%d uses Administering-owned token %s directly in %s; expected /ea/%s/%s...', $relativePath, $line, $rootToken, $uri, $coreRootToken, $rootToken);
            }
        }
    }
}

if (!$hasCoreRootRoute) {
    $findings[] = sprintf('No /ea/%s route was found. Administering EasyAdmin routes must use the composer extra.core-entity root token.', $coreRootToken);
}

if ([] !== $findings) {
    fwrite(STDERR, "EasyAdmin route root token guard failed:\n");
    foreach ($findings as $finding) {
        fwrite(STDERR, ' - '.$finding."\n");
    }
    exit(1);
}

fwrite(STDOUT, "EasyAdmin route root token guard passed.\n");
exit(0);
