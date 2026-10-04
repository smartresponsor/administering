<?php

declare(strict_types=1);

namespace App\Administering\Tests\Command;

use App\Administering\CatalogInterface\Admin\AdministrationServiceToolCatalogInterface;
use App\Administering\Command\AdministrationOwnerConfigurationToolTransitionPauseGateCommand;
use App\Administering\Value\Admin\AdministrationServiceTool;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class AdministrationOwnerConfigurationToolTransitionPauseGateCommandTest extends TestCase
{
    public function testMissingTransitionArtifactsFailClosedWithDeterministicOwnerCandidateReport(): void
    {
        $tool = new AdministrationServiceTool(
            section: 'Billing',
            directionToken: 'billing',
            toolSlug: 'Tax',
            toolKey: 'billing.tax',
            serviceClass: 'App\\Administering\\Service\\Billing\\AdministrationBillingTaxService',
            shortName: 'AdministrationBillingTaxService',
            serviceFile: 'src/Service/Billing/AdministrationBillingTaxService.php',
            label: 'Tax',
            kind: 'configuration',
            operationType: 'read',
            checksum: 'checksum',
            ownerComponentKey: 'Billing',
            ownerComponentToken: 'billing',
        );

        $tester = new CommandTester(new AdministrationOwnerConfigurationToolTransitionPauseGateCommand(
            $this->catalog([$tool]),
            sys_get_temp_dir().'/administering-transition-pause-gate-missing',
        ));

        self::assertSame(Command::FAILURE, $tester->execute([
            'component' => 'billing',
            '--json' => true,
            '--fail-if-not-ready' => true,
        ]));

        $report = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(1, $report['toolCount']);
        self::assertSame(1, $report['ownerRepositoryCandidateCount']);
        self::assertSame(4, $report['warningCount']);
        self::assertFalse($report['canPauseInternalWaves']);
        self::assertSame('finish_transition_infrastructure', $report['nextWorkMode']);
        self::assertSame('owner_repository_candidate', $report['classifications'][0]['classification']);
        self::assertSame(
            'Billing/src/Service/Configuration/BillingConfigurationTaxService.php',
            $report['classifications'][0]['recommendedNextTarget'],
        );
        self::assertSame('external_pipeline_report_missing', $report['issues'][0]['code']);
    }

    /**
     * @param list<AdministrationServiceTool> $tools
     */
    private function catalog(array $tools): AdministrationServiceToolCatalogInterface
    {
        return new class($tools) implements AdministrationServiceToolCatalogInterface {
            /**
             * @param list<AdministrationServiceTool> $tools
             */
            public function __construct(private readonly array $tools)
            {
            }

            public function tools(): array
            {
                return $this->tools;
            }

            public function toolsForSection(string $section): array
            {
                return array_values(array_filter(
                    $this->tools,
                    static fn (AdministrationServiceTool $tool): bool => $tool->section === $section,
                ));
            }
        };
    }
}
