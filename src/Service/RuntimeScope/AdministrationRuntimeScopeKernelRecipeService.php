<?php

declare(strict_types=1);

namespace App\Administering\Service\RuntimeScope;

use App\Administering\Value\RuntimeScope\AdministrationRuntimeScopeKernelRecipeRequest;
use App\Administering\Value\RuntimeScope\AdministrationRuntimeScopeKernelRecipeResult;

final class AdministrationRuntimeScopeKernelRecipeService
{
    public function __construct(private readonly string $projectDir)
    {
    }

    public function install(AdministrationRuntimeScopeKernelRecipeRequest $request): AdministrationRuntimeScopeKernelRecipeResult
    {
        $hostDir = $this->absolutePath($request->hostDir);
        $actions = [];
        $errors = [];

        if (!is_dir($hostDir)) {
            $errors[] = sprintf('Host directory does not exist: %s', $hostDir);
        }

        $composerInventory = $this->composerInventoryReport($hostDir);

        $plannedFiles = $this->plannedFiles($hostDir);
        foreach ($plannedFiles as $relativePath => $content) {
            $targetPath = $hostDir.'/'.$relativePath;
            $exists = is_file($targetPath);
            $status = $exists ? ($request->force ? 'overwrite' : 'keep_existing') : 'create';
            $actions[] = [
                'type' => 'file',
                'path' => $relativePath,
                'status' => $status,
            ];

            if ($request->apply && (!$exists || $request->force)) {
                $directory = dirname($targetPath);
                if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
                    $errors[] = sprintf('Unable to create directory: %s', $directory);
                    continue;
                }

                file_put_contents($targetPath, $content);
            }
        }

        $kernelPatch = $this->kernelPatchPlan($hostDir);
        if (!$request->patchKernel) {
            $kernelPatch['status'] = 'skipped_by_option';
        }
        $actions[] = $kernelPatch;

        if ($request->apply && $request->patchKernel && 'patchable' === $kernelPatch['status']) {
            $kernelPath = $hostDir.'/src/Kernel.php';
            $original = (string) file_get_contents($kernelPath);
            $patched = $this->patchKernelSource($original);
            if ($patched === $original) {
                $errors[] = 'Kernel patch did not change src/Kernel.php. Review docs/recipe/runtime-scope-kernel/Kernel.runtime-scope.example.php.';
            } else {
                $backupPath = $kernelPath.'.runtime-scope-backup.'.date('YmdHis');
                copy($kernelPath, $backupPath);
                file_put_contents($kernelPath, $patched);
                $actions[count($actions) - 1]['backupPath'] = $this->relativePath($hostDir, $backupPath);
            }
        }

        return new AdministrationRuntimeScopeKernelRecipeResult(
            recipe: 'administration_runtime_scope_kernel_recipe',
            hostDir: $hostDir,
            apply: $request->apply,
            force: $request->force,
            kernelPatchRequested: $request->patchKernel,
            composerInventory: $composerInventory,
            actions: $actions,
            errors: $errors,
            nextActions: [
                'Move profile-controlled Smart Responsor bundles out of config/bundles.php and into config/kernel/runtime_scope*.lock.php when the host is ready.',
                'Keep Symfony/system/dev-only bundles in config/bundles.php.',
                'Regenerate runtime scope locks after composer.json or composer.prod.json changes so sourceComposerSha256 stays current.',
                'Run cache clear/warmup after changing runtime scope locks.',
            ],
        );
    }

    /** @return array<string, string> */
    private function plannedFiles(string $hostDir): array
    {
        return [
            'src/Kernel/RuntimeComposerInventoryReader.php' => $this->runtimeComposerInventoryReaderSource(),
            'src/Kernel/RuntimeCompositionLockReader.php' => $this->runtimeCompositionLockReaderSource(),
            'src/Kernel/RuntimeBundleIterator.php' => $this->runtimeBundleIteratorSource(),
            'config/kernel/runtime_scope.lock.php' => $this->runtimeScopeLockSource($hostDir, 'default', 'composer.json', false),
            'config/kernel/runtime_scope.prod.lock.php' => $this->runtimeScopeLockSource($hostDir, 'production', 'composer.prod.json', true),
            'docs/recipe/runtime-scope-kernel/Kernel.runtime-scope.example.php' => $this->kernelExampleSource(),
            'docs/recipe/runtime-scope-kernel/README.adoc' => $this->recipeReadmeSource(),
        ];
    }

    /** @return array<string, string> */
    private function kernelPatchPlan(string $hostDir): array
    {
        $kernelPath = $hostDir.'/src/Kernel.php';
        if (!is_file($kernelPath)) {
            return [
                'type' => 'kernel_patch',
                'path' => 'src/Kernel.php',
                'status' => 'missing',
            ];
        }

        $source = (string) file_get_contents($kernelPath);
        if (str_contains($source, 'RuntimeBundleIterator::fromProjectDir')) {
            return [
                'type' => 'kernel_patch',
                'path' => 'src/Kernel.php',
                'status' => 'already_patched',
            ];
        }

        if (!$this->canPatchKernelSource($source)) {
            return [
                'type' => 'kernel_patch',
                'path' => 'src/Kernel.php',
                'status' => 'manual_review_required',
            ];
        }

        return [
            'type' => 'kernel_patch',
            'path' => 'src/Kernel.php',
            'status' => 'patchable',
        ];
    }

    private function canPatchKernelSource(string $source): bool
    {
        return 1 === preg_match('/public\s+function\s+registerBundles\s*\(\s*\)\s*:\s*iterable\s*\{/m', $source);
    }

    private function patchKernelSource(string $source): string
    {
        $pattern = '/public\s+function\s+registerBundles\s*\(\s*\)\s*:\s*iterable\s*\{(?P<body>.*?)^    \}/ms';
        $replacement = <<<'PHP'
public function registerBundles(): iterable
    {
        $bundles = require $this->getProjectDir().'/config/bundles.php';

        foreach ($bundles as $class => $environments) {
            if (($environments[$this->environment] ?? $environments['all'] ?? false) && class_exists($class)) {
                yield new $class();
            }
        }

        yield from \App\Kernel\RuntimeBundleIterator::fromProjectDir($this->getProjectDir(), $this->environment);
    }
PHP;

        $patched = preg_replace($pattern, $replacement, $source, 1);

        return is_string($patched) ? $patched : $source;
    }

    private function runtimeComposerInventoryReaderSource(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Kernel;

final class RuntimeComposerInventoryReader
{
    /** @return array{path: string, file: string, exists: bool, sha256: string|null, requiredPackages: list<string>} */
    public static function read(string $projectDir, string $environment): array
    {
        $file = self::composerFile($environment);
        $path = rtrim($projectDir, '/\\').'/'.$file;

        if (!is_file($path)) {
            return [
                'path' => $path,
                'file' => $file,
                'exists' => false,
                'sha256' => null,
                'requiredPackages' => [],
            ];
        }

        $json = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($json)) {
            throw new \RuntimeException(sprintf('Composer inventory file must decode to an object: %s', $path));
        }

        $require = $json['require'] ?? [];
        if (!is_array($require)) {
            throw new \RuntimeException(sprintf('Composer inventory require section must be an object: %s', $path));
        }

        $packages = [];
        foreach (array_keys($require) as $packageName) {
            if (is_string($packageName) && 'php' !== $packageName) {
                $packages[] = $packageName;
            }
        }
        sort($packages);

        return [
            'path' => $path,
            'file' => $file,
            'exists' => true,
            'sha256' => hash_file('sha256', $path) ?: null,
            'requiredPackages' => $packages,
        ];
    }

    public static function composerFile(string $environment): string
    {
        return 'prod' === $environment ? 'composer.prod.json' : 'composer.json';
    }
}
PHP;
    }

    private function runtimeCompositionLockReaderSource(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Kernel;

final class RuntimeCompositionLockReader
{
    /**
     * @return array{path: string, scope: string, strict: bool, enabledBundles: list<class-string>}
     */
    public static function read(string $projectDir, string $environment): array
    {
        $path = self::lockPath($projectDir, $environment);
        $strictDefault = 'prod' === $environment;

        if (!is_file($path)) {
            if ($strictDefault) {
                throw new \RuntimeException(sprintf('Runtime scope lock file is missing: %s', $path));
            }

            return [
                'path' => $path,
                'scope' => 'missing_non_prod_lock',
                'strict' => false,
                'enabledBundles' => [],
            ];
        }

        $lock = require $path;
        if (!is_array($lock)) {
            throw new \RuntimeException(sprintf('Runtime scope lock file must return an array: %s', $path));
        }

        $enabledBundles = $lock['enabledBundles'] ?? [];
        if (!is_array($enabledBundles)) {
            throw new \RuntimeException(sprintf('Runtime scope lock enabledBundles must be an array: %s', $path));
        }

        $bundleClasses = [];
        foreach ($enabledBundles as $bundleClass) {
            if (!is_string($bundleClass) || '' === $bundleClass) {
                throw new \RuntimeException(sprintf('Runtime scope lock contains an invalid bundle class in %s.', $path));
            }
            $bundleClasses[] = $bundleClass;
        }

        $strict = is_bool($lock['strict'] ?? null) ? $lock['strict'] : $strictDefault;
        $composerInventory = RuntimeComposerInventoryReader::read($projectDir, $environment);
        $expectedComposerFile = RuntimeComposerInventoryReader::composerFile($environment);
        $sourceComposerFile = is_string($lock['sourceComposerFile'] ?? null) ? $lock['sourceComposerFile'] : null;
        if ($strict && $sourceComposerFile !== $expectedComposerFile) {
            throw new \RuntimeException(sprintf(
                'Runtime scope lock %s expects sourceComposerFile "%s", got "%s".',
                $path,
                $expectedComposerFile,
                $sourceComposerFile ?? 'null',
            ));
        }

        if ($strict && !$composerInventory['exists']) {
            throw new \RuntimeException(sprintf('Runtime scope composer inventory file is missing: %s', $composerInventory['path']));
        }

        $sourceComposerSha256 = is_string($lock['sourceComposerSha256'] ?? null) ? $lock['sourceComposerSha256'] : null;
        if ($strict && null !== $sourceComposerSha256 && $composerInventory['sha256'] !== $sourceComposerSha256) {
            throw new \RuntimeException(sprintf(
                'Runtime scope composer inventory fingerprint mismatch for %s. Regenerate %s.',
                $composerInventory['file'],
                basename($path),
            ));
        }

        return [
            'path' => $path,
            'scope' => is_string($lock['scope'] ?? null) ? $lock['scope'] : basename($path),
            'strict' => $strict,
            'enabledBundles' => $bundleClasses,
        ];
    }

    public static function lockPath(string $projectDir, string $environment): string
    {
        $fileName = 'prod' === $environment ? 'runtime_scope.prod.lock.php' : 'runtime_scope.lock.php';

        return rtrim($projectDir, '/\\').'/config/kernel/'.$fileName;
    }
}
PHP;
    }

    private function runtimeBundleIteratorSource(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Kernel;

use Symfony\Component\DependencyInjection\Kernel\BundleInterface;

final class RuntimeBundleIterator
{
    /** @return iterable<BundleInterface> */
    public static function fromProjectDir(string $projectDir, string $environment): iterable
    {
        $lock = RuntimeCompositionLockReader::read($projectDir, $environment);

        foreach ($lock['enabledBundles'] as $bundleClass) {
            if (!class_exists($bundleClass)) {
                if ($lock['strict']) {
                    throw new \RuntimeException(sprintf(
                        'Runtime scope bundle class "%s" is missing for scope "%s" from %s.',
                        $bundleClass,
                        $lock['scope'],
                        $lock['path'],
                    ));
                }

                continue;
            }

            $bundle = new $bundleClass();
            if (!$bundle instanceof BundleInterface) {
                throw new \RuntimeException(sprintf('Runtime scope class "%s" is not a Symfony bundle.', $bundleClass));
            }

            yield $bundle;
        }
    }
}
PHP;
    }

    private function runtimeScopeLockSource(string $hostDir, string $scope, string $sourceComposerFile, bool $strict): string
    {
        $strictLiteral = $strict ? 'true' : 'false';
        $composerPath = rtrim($hostDir, '/\\').'/'.$sourceComposerFile;
        $sourceComposerSha256 = is_file($composerPath) ? hash_file('sha256', $composerPath) : null;
        $sourceComposerSha256Literal = null === $sourceComposerSha256 ? 'null' : var_export($sourceComposerSha256, true);
        $sourceComposerPackageCount = $this->composerPackageCount($composerPath);

        return <<<PHP
<?php

declare(strict_types=1);

return [
    'scope' => '{$scope}',
    'sourceComposerFile' => '{$sourceComposerFile}',
    'sourceComposerSha256' => {$sourceComposerSha256Literal},
    'sourceComposerPackageCount' => {$sourceComposerPackageCount},
    'strict' => {$strictLiteral},
    'enabledBundles' => [
        // Add Smart Responsor runtime bundles here after installation/export.
    ],
    'disabledComponents' => [],
    'generatedBy' => 'administering:runtime-scope:install-kernel-recipe',
];
PHP;
    }

    private function kernelExampleSource(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        $bundles = require $this->getProjectDir().'/config/bundles.php';

        foreach ($bundles as $class => $environments) {
            if (($environments[$this->environment] ?? $environments['all'] ?? false) && class_exists($class)) {
                yield new $class();
            }
        }

        yield from \App\Kernel\RuntimeBundleIterator::fromProjectDir($this->getProjectDir(), $this->environment);
    }
}
PHP;
    }

    private function recipeReadmeSource(): string
    {
        return <<<'ADOC'
= Runtime Scope Kernel Recipe

Administering owns runtime-scope decisions and lock generation, but the host App owns the Kernel boot hook.

The recipe installs:

* `src/Kernel/RuntimeComposerInventoryReader.php`
* `src/Kernel/RuntimeCompositionLockReader.php`
* `src/Kernel/RuntimeBundleIterator.php`
* `config/kernel/runtime_scope.lock.php`
* `config/kernel/runtime_scope.prod.lock.php`

The Kernel reads `runtime_scope.prod.lock.php` only when `APP_ENV=prod`; otherwise it reads `runtime_scope.lock.php`.

The Kernel-side reader also reads `composer.prod.json` for `APP_ENV=prod` and `composer.json` for non-prod as physical inventory/fingerprint input. Composer files are not the source of enabled bundles; they only prove the lock was generated against the expected inventory.

`config/bundles.php` should keep Symfony/system/dev-only bundles. Profile-controlled Smart Responsor bundles belong in the runtime scope lock files.
ADOC;
    }

    /** @return array<string, array{status: string, path: string, sha256: string|null, packageCount: int}> */
    private function composerInventoryReport(string $hostDir): array
    {
        return [
            'default' => $this->composerInventoryFileReport($hostDir, 'composer.json'),
            'production' => $this->composerInventoryFileReport($hostDir, 'composer.prod.json'),
        ];
    }

    /** @return array{status: string, path: string, sha256: string|null, packageCount: int} */
    private function composerInventoryFileReport(string $hostDir, string $fileName): array
    {
        $path = rtrim($hostDir, '/\\').'/'.$fileName;
        if (!is_file($path)) {
            return [
                'status' => 'missing',
                'path' => $path,
                'sha256' => null,
                'packageCount' => 0,
            ];
        }

        return [
            'status' => sprintf('present (%d packages)', $this->composerPackageCount($path)),
            'path' => $path,
            'sha256' => hash_file('sha256', $path) ?: null,
            'packageCount' => $this->composerPackageCount($path),
        ];
    }

    private function composerPackageCount(string $composerPath): int
    {
        if (!is_file($composerPath)) {
            return 0;
        }

        try {
            $json = json_decode((string) file_get_contents($composerPath), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return 0;
        }

        if (!is_array($json) || !is_array($json['require'] ?? null)) {
            return 0;
        }

        return count(array_filter(
            array_keys($json['require']),
            static fn (string $packageName): bool => 'php' !== $packageName,
        ));
    }

    private function absolutePath(string $path): string
    {
        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\/]/', $path)) {
            return rtrim($path, '/\\');
        }

        return rtrim($this->projectDir, '/\\').'/'.trim($path, '/\\');
    }

    private function relativePath(string $root, string $path): string
    {
        $root = rtrim(str_replace('\\', '/', $root), '/').'/';
        $path = str_replace('\\', '/', $path);

        return str_starts_with($path, $root) ? substr($path, strlen($root)) : $path;
    }
}
