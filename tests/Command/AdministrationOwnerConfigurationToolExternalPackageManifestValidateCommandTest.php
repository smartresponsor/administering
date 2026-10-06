<?php

declare(strict_types=1);

namespace App\Administering\Tests\Command;

use App\Administering\Command\AdministrationOwnerConfigurationToolExternalPackageManifestValidateCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class AdministrationOwnerConfigurationToolExternalPackageManifestValidateCommandTest extends TestCase
{
    public function testValidManifestSucceeds(): void
    {
        $component = $this->componentManifest();
        $manifest = $this->writeManifest([
            'schema' => 'smart-responsor.administering.owner_configuration_external_package_manifest.v1',
            'deliveryMode' => 'overlay_only',
            'deleteMode' => 'none',
            'automaticMoveAllowed' => false,
            'componentManifests' => [$component],
            'rejectedEntries' => [],
        ]);

        try {
            $tester = new CommandTester(new AdministrationOwnerConfigurationToolExternalPackageManifestValidateCommand());
            self::assertSame(Command::SUCCESS, $tester->execute(['manifest' => $manifest, '--json' => true]));
        } finally {
            @unlink($manifest);
        }
    }

    public function testDuplicateOverlayTargetFails(): void
    {
        $component = $this->componentManifest();
        $manifest = $this->writeManifest([
            'schema' => 'smart-responsor.administering.owner_configuration_external_package_manifest.v1',
            'deliveryMode' => 'overlay_only',
            'deleteMode' => 'none',
            'automaticMoveAllowed' => false,
            'componentManifests' => [$component, $component],
            'rejectedEntries' => [],
        ]);

        try {
            $tester = new CommandTester(new AdministrationOwnerConfigurationToolExternalPackageManifestValidateCommand());
            self::assertSame(Command::FAILURE, $tester->execute(['manifest' => $manifest, '--json' => true]));
            self::assertStringContainsString('Duplicate overlay target', $tester->getDisplay());
        } finally {
            @unlink($manifest);
        }
    }

    /** @return array<string, mixed> */
    private function componentManifest(): array
    {
        return [
            'componentKey' => 'Billing',
            'componentToken' => 'billing',
            'providerClass' => 'App\\Billing\\Provider\\BillingConfigurationToolProvider',
            'deliveryMode' => 'overlay_only',
            'deleteMode' => 'none',
            'automaticMoveAllowed' => false,
            'files' => ['Billing/src/Service/Configuration/BillingConfigurationTaxService.php'],
            'tools' => [[
                'toolKey' => 'billing.tax',
                'toolSlug' => 'Tax',
                'serviceShortName' => 'BillingConfigurationTaxService',
                'servicePath' => 'Billing/src/Service/Configuration/BillingConfigurationTaxService.php',
                'copyMode' => 'overlay_only',
                'deleteMode' => 'none',
                'automaticMoveAllowed' => false,
            ]],
        ];
    }

    /** @param array<string, mixed> $payload */
    private function writeManifest(array $payload): string
    {
        $path = tempnam(sys_get_temp_dir(), 'administering-manifest-');
        self::assertNotFalse($path);
        file_put_contents($path, json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

        return $path;
    }
}
