<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Service\Config;

use App\Administering\Service\Config\AdministrationConfigFileWriterService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

final class AdministrationConfigFileWriterServiceTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'administering-config-writer-'.bin2hex(random_bytes(6));
        self::assertTrue(mkdir($this->directory, 0777, true));
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->directory);
    }

    public function testRejectsPathOutsideWriteWhitelist(): void
    {
        $result = (new AdministrationConfigFileWriterService($this->directory))
            ->write('', 'config/app.yaml', ['enabled' => true], ['config/other.yaml']);

        self::assertSame('failed', $result['status']);
        self::assertSame('config/app.yaml', $result['path']);
        self::assertNull($result['backup_path']);
        self::assertSame('Path is not whitelisted for configuration writes.', $result['message']);
    }

    public function testRejectsMissingWhitelistedFile(): void
    {
        $result = (new AdministrationConfigFileWriterService($this->directory))
            ->write('', 'config/app.yaml', ['enabled' => true], ['config/app.yaml']);

        self::assertSame('failed', $result['status']);
        self::assertNull($result['backup_path']);
        self::assertSame('Configuration file does not exist.', $result['message']);
    }

    public function testUsesProjectDirectoryFallbackAndNormalizesScalarYaml(): void
    {
        $this->writeFixture('config/scalar.yaml', "scalar-value\n");

        $result = (new AdministrationConfigFileWriterService($this->directory))
            ->write('', 'config/scalar.yaml', ['enabled' => true], ['config/scalar.yaml']);

        self::assertSame('applied', $result['status']);
        self::assertSame('Configuration file updated.', $result['message']);
        self::assertSame(['enabled' => true], Yaml::parseFile($this->directory.'/config/scalar.yaml'));
        self::assertSame("scalar-value\n", file_get_contents((string) $result['backup_path']));
    }

    public function testDeepMergesNestedYamlAndPreservesSiblingKeys(): void
    {
        $original = [
            'framework' => [
                'router' => [
                    'utf8' => false,
                    'strict_requirements' => true,
                ],
                'secret' => 'existing',
            ],
            'unchanged' => 'value',
        ];
        $this->writeFixture('config/app.yaml', Yaml::dump($original, 6, 2));

        $result = (new AdministrationConfigFileWriterService('D:/unused'))
            ->write(
                $this->directory,
                'config/app.yaml',
                [
                    'framework' => [
                        'router' => [
                            'utf8' => true,
                        ],
                        'session' => [
                            'enabled' => true,
                        ],
                    ],
                    'new_key' => null,
                ],
                ['config/app.yaml'],
            );

        self::assertSame('applied', $result['status']);
        self::assertSame(
            str_replace('\\', '/', $this->directory.'/config/app.yaml.bak'),
            str_replace('\\', '/', (string) $result['backup_path']),
        );

        $written = Yaml::parseFile($this->directory.'/config/app.yaml');
        self::assertSame([
            'framework' => [
                'router' => [
                    'utf8' => true,
                    'strict_requirements' => true,
                ],
                'secret' => 'existing',
                'session' => [
                    'enabled' => true,
                ],
            ],
            'unchanged' => 'value',
            'new_key' => null,
        ], $written);
        self::assertSame($original, Yaml::parseFile((string) $result['backup_path']));
    }

    private function writeFixture(string $relativePath, string $contents): void
    {
        $path = $this->directory.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $directory = dirname($path);
        if (!is_dir($directory)) {
            self::assertTrue(mkdir($directory, 0777, true));
        }

        self::assertNotFalse(file_put_contents($path, $contents));
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
                continue;
            }

            unlink($item->getPathname());
        }

        rmdir($directory);
    }
}
