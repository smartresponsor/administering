<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$errors = [];

$mustNotExist = [
    'config/services.standalone.yaml',
    'config/services.dev.yaml',
    'config/services.prod.yaml',
    'config/services_prod.yaml',
];

foreach ($mustNotExist as $relativePath) {
    if (is_file($root.'/'.$relativePath)) {
        $errors[] = sprintf('Unexpected legacy service env file exists: %s', $relativePath);
    }
}

$requiredFiles = [
    'config/services.yaml',
    'config/services_dev.yaml',
    'src/Kernel.php',
    'composer.json',
];

foreach ($requiredFiles as $relativePath) {
    if (!is_file($root.'/'.$relativePath)) {
        $errors[] = sprintf('Required Symfony 8.1 readiness file is missing: %s', $relativePath);
    }
}

$forbiddenNeedles = [
    'Symfony\\Component\\HttpKernel\\Bundle\\BundleInterface',
    'Symfony\\Component\\HttpKernel\\DependencyInjection\\MergeExtensionConfigurationPass',
    'Symfony\\Component\\HttpKernel\\Config\\FileLocator',
];

$scanDirectories = [
    'src',
    'config',
    'tools',
];

foreach ($scanDirectories as $directory) {
    $base = $root.'/'.$directory;
    if (!is_dir($base)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (!$file instanceof SplFileInfo || !$file->isFile()) {
            continue;
        }

        $extension = strtolower($file->getExtension());
        if (!in_array($extension, ['php', 'yaml', 'yml', 'xml', 'dist'], true)) {
            continue;
        }

        $content = file_get_contents($file->getPathname());
        if (!is_string($content)) {
            continue;
        }

        foreach ($forbiddenNeedles as $needle) {
            if (str_contains($content, $needle)) {
                $errors[] = sprintf('Deprecated Symfony 8.1 moved class reference found in %s: %s', str_replace($root.'/', '', $file->getPathname()), $needle);
            }
        }
    }
}

$kernelPath = $root.'/src/Kernel.php';
if (is_file($kernelPath)) {
    $kernel = file_get_contents($kernelPath) ?: '';

    if (!str_contains($kernel, "use Symfony\\Component\\DependencyInjection\\Kernel\\BundleInterface;")) {
        $errors[] = 'Kernel must use DependencyInjection\\Kernel\\BundleInterface for Symfony 8.1.';
    }

    if (!str_contains($kernel, "../config/services.yaml")) {
        $errors[] = 'Kernel must import config/services.yaml as the base/prod-by-default DI graph.';
    }

    if (!str_contains($kernel, "../config/services_'.\$this->environment.'.yaml")) {
        $errors[] = 'Kernel must import optional native config/services_<env>.yaml overlay.';
    }
}

$servicesPath = $root.'/config/services.yaml';
if (is_file($servicesPath)) {
    $services = file_get_contents($servicesPath) ?: '';
    if (!str_contains($services, 'app.connected_components: []')) {
        $errors[] = 'config/services.yaml must define a safe dry-runtime default for app.connected_components.';
    }
}

$composerPath = $root.'/composer.json';
if (is_file($composerPath)) {
    $composer = json_decode(file_get_contents($composerPath) ?: '', true);
    if (!is_array($composer)) {
        $errors[] = 'composer.json is not valid JSON.';
    } else {
        foreach (['require', 'require-dev'] as $section) {
            foreach (($composer[$section] ?? []) as $package => $constraint) {
                if (!is_string($package) || !str_starts_with($package, 'symfony/')) {
                    continue;
                }

                if (in_array($package, ['symfony/stimulus-bundle', 'symfony/ux-turbo', 'symfony/maker-bundle'], true)) {
                    continue;
                }

                if ('^8.1' !== $constraint) {
                    $errors[] = sprintf('Symfony 8.1 package constraint expected for %s: got %s', $package, is_scalar($constraint) ? (string) $constraint : gettype($constraint));
                }
            }
        }
    }
}

if ([] !== $errors) {
    fwrite(STDERR, "Administering Symfony 8.1 readiness guard failed:\n");
    foreach ($errors as $error) {
        fwrite(STDERR, ' - '.$error."\n");
    }
    exit(1);
}

echo "Administering Symfony 8.1 readiness guard passed.\n";
