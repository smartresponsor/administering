<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$playwrightReportPath = $root.'/var/coverage/playwright.json';
$coverageMapPath = $root.'/tests/coverage/behavioral-ui-map.json';
$outputPath = $root.'/var/coverage/behavioral-ui.json';

$decodeJsonFile = static function (string $path): array {
    if (!is_file($path)) {
        throw new RuntimeException(sprintf('Required coverage input is missing: %s', $path));
    }

    $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($decoded)) {
        throw new UnexpectedValueException(sprintf('Coverage input must decode to an array/object: %s', $path));
    }

    return $decoded;
};

$playwrightReport = $decodeJsonFile($playwrightReportPath);
$coverageMap = $decodeJsonFile($coverageMapPath);

$routerCommand = sprintf(
    '%s %s debug:router --format=json --env=dev --no-interaction',
    escapeshellarg(PHP_BINARY),
    escapeshellarg($root.'/bin/console'),
);
$routeOutput = [];
$routeExitCode = 0;
exec($routerCommand, $routeOutput, $routeExitCode);
if (0 !== $routeExitCode) {
    throw new RuntimeException(sprintf('Unable to inventory Symfony routes; debug:router exited %d.', $routeExitCode));
}

$routes = json_decode(implode("\n", $routeOutput), true, 512, JSON_THROW_ON_ERROR);
if (!is_array($routes)) {
    throw new UnexpectedValueException('Symfony route inventory must decode to an object.');
}

$functionalEligible = [];
$uiEligible = [];
foreach ($routes as $routeName => $route) {
    if (!is_string($routeName) || !is_array($route)) {
        continue;
    }

    $defaults = is_array($route['defaults'] ?? null) ? $route['defaults'] : [];
    $controller = $defaults['_controller'] ?? null;
    if (!is_string($controller) || !str_starts_with($controller, 'App\\Administering\\Controller\\')) {
        continue;
    }

    $method = strtoupper((string) ($route['method'] ?? 'ANY'));
    if ('ANY' !== $method && !in_array('GET', preg_split('/\|/', $method) ?: [], true)) {
        continue;
    }

    $functionalEligible[] = 'route:'.$routeName;

    $path = (string) ($route['path'] ?? '');
    if (str_starts_with($path, '/ea/administration')) {
        $uiEligible[] = 'surface:'.$routeName;
    }
}

sort($functionalEligible);
sort($uiEligible);

$passedTitles = [];
$collectPassed = static function (array $suite) use (&$collectPassed, &$passedTitles): void {
    foreach (($suite['specs'] ?? []) as $spec) {
        if (!is_array($spec) || true !== ($spec['ok'] ?? false) || !is_string($spec['title'] ?? null)) {
            continue;
        }

        $passedTitles[$spec['title']] = true;
    }

    foreach (($suite['suites'] ?? []) as $childSuite) {
        if (is_array($childSuite)) {
            $collectPassed($childSuite);
        }
    }
};

foreach (($playwrightReport['suites'] ?? []) as $suite) {
    if (is_array($suite)) {
        $collectPassed($suite);
    }
}

$eligible = [
    'functional' => array_values(array_unique($functionalEligible)),
    'behavioral' => array_values(array_unique(array_filter($coverageMap['eligible']['behavioral'] ?? [], 'is_string'))),
    'ui' => array_values(array_unique($uiEligible)),
    'critical' => array_values(array_unique(array_filter($coverageMap['eligible']['critical'] ?? [], 'is_string'))),
];

$covered = [
    'functional' => [],
    'behavioral' => [],
    'ui' => [],
    'critical' => [],
];

foreach (($coverageMap['tests'] ?? []) as $testTitle => $dimensions) {
    if (!is_string($testTitle) || !isset($passedTitles[$testTitle]) || !is_array($dimensions)) {
        continue;
    }

    foreach (array_keys($covered) as $dimension) {
        foreach (($dimensions[$dimension] ?? []) as $identifier) {
            if (!is_string($identifier) || !in_array($identifier, $eligible[$dimension], true)) {
                continue;
            }

            $covered[$dimension][] = $identifier;
        }
    }
}

foreach ($covered as $dimension => $identifiers) {
    $covered[$dimension] = array_values(array_unique($identifiers));
    sort($covered[$dimension]);
}

$evidence = [
    'schema' => 'behavioral-ui-coverage-v2',
    'generatedAt' => (new DateTimeImmutable())->format(DATE_ATOM),
    'producer' => [
        'kind' => 'repository_script',
        'script' => 'test:behavioral-coverage',
    ],
    'dimensions' => [],
];

foreach (array_keys($eligible) as $dimension) {
    $evidence['dimensions'][$dimension] = [
        'eligible' => $eligible[$dimension],
        'covered' => $covered[$dimension],
    ];
}

if (!is_dir(dirname($outputPath)) && !mkdir(dirname($outputPath), 0777, true) && !is_dir(dirname($outputPath))) {
    throw new RuntimeException(sprintf('Unable to create coverage output directory: %s', dirname($outputPath)));
}

file_put_contents(
    $outputPath,
    json_encode($evidence, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n",
);

echo sprintf(
    "Behavioral/UI coverage evidence written: functional %d/%d; behavioral %d/%d; ui %d/%d; critical %d/%d.\n",
    count($covered['functional']),
    count($eligible['functional']),
    count($covered['behavioral']),
    count($eligible['behavioral']),
    count($covered['ui']),
    count($eligible['ui']),
    count($covered['critical']),
    count($eligible['critical']),
);
