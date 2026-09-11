<?php

declare(strict_types=1);

$guard = __DIR__.'/administering-owner-component-coupling-guard.php';
if (!is_file($guard)) {
    fwrite(STDERR, "Owner component coupling guard is missing.\n");
    exit(2);
}

$fixtureRoot = sys_get_temp_dir().DIRECTORY_SEPARATOR.'administering-owner-coupling-'.bin2hex(random_bytes(8));
$sourceDirectory = $fixtureRoot.DIRECTORY_SEPARATOR.'src';

$removeFixture = static function (string $directory) use (&$removeFixture): void {
    if (!is_dir($directory)) {
        return;
    }

    $items = scandir($directory);
    if (false === $items) {
        return;
    }

    foreach ($items as $item) {
        if ('.' === $item || '..' === $item) {
            continue;
        }

        $path = $directory.DIRECTORY_SEPARATOR.$item;
        if (is_dir($path)) {
            $removeFixture($path);
            continue;
        }

        unlink($path);
    }

    rmdir($directory);
};

$runGuard = static function (string $root) use ($guard): array {
    $command = escapeshellarg(PHP_BINARY).' '.escapeshellarg($guard).' '.escapeshellarg($root).' 2>&1';
    $output = [];
    $exitCode = 0;
    exec($command, $output, $exitCode);

    return [$exitCode, implode("\n", $output)];
};

try {
    if (!mkdir($sourceDirectory, 0777, true) && !is_dir($sourceDirectory)) {
        throw new RuntimeException('Unable to create regression fixture directory.');
    }

    $violatingSource = <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Administering\Probe;

use App\Cruding\Service\CrudProcessor;

final class ViolatingProbe
{
}
PHP;

    $fixtureFile = $sourceDirectory.DIRECTORY_SEPARATOR.'ViolatingProbe.php';
    if (false === file_put_contents($fixtureFile, $violatingSource)) {
        throw new RuntimeException('Unable to write violating regression fixture.');
    }

    [$violatingExitCode, $violatingOutput] = $runGuard($fixtureRoot);
    if (1 !== $violatingExitCode) {
        throw new RuntimeException(sprintf(
            'Expected owner-component coupling violation to exit 1, got %d. Output: %s',
            $violatingExitCode,
            $violatingOutput,
        ));
    }

    foreach (['owner component coupling guard failed', 'src/ViolatingProbe.php', 'App\\Cruding\\'] as $expected) {
        if (!str_contains($violatingOutput, $expected)) {
            throw new RuntimeException(sprintf(
                'Violation diagnostic is missing %s. Output: %s',
                $expected,
                $violatingOutput,
            ));
        }
    }

    $cleanSource = <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Administering\Probe;

final class CleanProbe
{
}
PHP;

    if (false === file_put_contents($fixtureFile, $cleanSource)) {
        throw new RuntimeException('Unable to write clean regression fixture.');
    }

    [$cleanExitCode, $cleanOutput] = $runGuard($fixtureRoot);
    if (0 !== $cleanExitCode) {
        throw new RuntimeException(sprintf(
            'Expected clean owner-component fixture to exit 0, got %d. Output: %s',
            $cleanExitCode,
            $cleanOutput,
        ));
    }

    if (!str_contains($cleanOutput, 'owner component coupling guard passed')) {
        throw new RuntimeException('Clean fixture did not emit the expected pass diagnostic.');
    }
} catch (Throwable $exception) {
    fwrite(STDERR, 'Owner component coupling guard regression check failed: '.$exception->getMessage().PHP_EOL);
    exit(1);
} finally {
    $removeFixture($fixtureRoot);
}

fwrite(STDOUT, "Owner component coupling guard regression check passed.\n");
exit(0);
