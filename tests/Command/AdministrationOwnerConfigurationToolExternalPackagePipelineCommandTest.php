<?php

declare(strict_types=1);

namespace App\Administering\Tests\Command;

use App\Administering\Command\AdministrationOwnerConfigurationToolExternalPackagePipelineCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

final class AdministrationOwnerConfigurationToolExternalPackagePipelineCommandTest extends TestCase
{
    private const STEP_COMMANDS = [
        'administering:owner-configuration-tools:external-package-manifest',
        'administering:owner-configuration-tools:external-package-manifest:validate',
        'administering:owner-configuration-tools:external-package-overlay-plan',
        'administering:owner-configuration-tools:external-package-apply-script',
        'administering:owner-configuration-tools:external-package-handoff-bundle',
        'administering:owner-configuration-tools:external-package-handoff-bundle:validate',
    ];

    public function testSuccessfulPipelineDispatchesEveryStepInOrder(): void
    {
        $calls = [];
        [$tester, $outputRoot] = $this->tester($calls);

        try {
            self::assertSame(Command::SUCCESS, $tester->execute([
                '--output-root' => $outputRoot,
                '--handoff-dir' => $outputRoot.'/handoff',
                '--json' => true,
            ]));
            self::assertSame(self::STEP_COMMANDS, $calls);
        } finally {
            @rmdir($outputRoot);
        }
    }

    public function testPipelineStopsOrContinuesAfterFailure(): void
    {
        foreach ([false, true] as $continueOnFailure) {
            $calls = [];
            [$tester, $outputRoot] = $this->tester($calls, [self::STEP_COMMANDS[1] => Command::FAILURE]);

            try {
                self::assertSame(Command::FAILURE, $tester->execute([
                    '--output-root' => $outputRoot,
                    '--handoff-dir' => $outputRoot.'/handoff',
                    '--continue-on-failure' => $continueOnFailure,
                    '--json' => true,
                ]));
                self::assertSame(
                    $continueOnFailure ? self::STEP_COMMANDS : array_slice(self::STEP_COMMANDS, 0, 2),
                    $calls,
                );
                self::assertStringContainsString('manifest_validation', $tester->getDisplay());
            } finally {
                @rmdir($outputRoot);
            }
        }
    }

    /**
     * @param list<string>       $calls
     * @param array<string, int> $exitCodes
     *
     * @return array{CommandTester, string}
     */
    private function tester(array &$calls, array $exitCodes = []): array
    {
        $application = new Application();
        $pipeline = new AdministrationOwnerConfigurationToolExternalPackagePipelineCommand();
        $application->addCommand($pipeline);

        foreach (self::STEP_COMMANDS as $commandName) {
            $application->addCommand(new AdministrationOwnerConfigurationToolExternalPackagePipelineRecordingCommand(
                $commandName,
                static function (?string $calledCommand) use (&$calls): void {
                    if (null !== $calledCommand) {
                        $calls[] = $calledCommand;
                    }
                },
                $exitCodes[$commandName] ?? Command::SUCCESS,
            ));
        }

        return [new CommandTester($pipeline), sys_get_temp_dir().'/administering-pipeline-'.bin2hex(random_bytes(8))];
    }
}

final class AdministrationOwnerConfigurationToolExternalPackagePipelineRecordingCommand extends Command
{
    public function __construct(
        string $name,
        private readonly \Closure $onRun,
        private readonly int $exitCode,
    ) {
        parent::__construct($name);
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        ($this->onRun)($this->getName());

        return $this->exitCode;
    }
}
