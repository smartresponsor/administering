<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Repository\Config;

use App\Administering\Entity\Config\AdministrationConfigApplicationEntity;
use App\Administering\Repository\Config\AdministrationConfigRegistryRepository;
use App\Administering\Value\Config\AdministrationConfigApplicationDescriptor;
use App\Administering\Value\Config\AdministrationConfigToolDescriptor;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

final class AdministrationConfigRegistryRepositoryTest extends TestCase
{
    public function testReplaceClearsRegistryAndCommitsEmptySnapshot(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('beginTransaction');
        $connection->expects(self::exactly(2))
            ->method('executeStatement')
            ->with(self::logicalOr(
                self::equalTo('DELETE FROM administration_config_tool'),
                self::equalTo('DELETE FROM administration_config_application'),
            ));
        $connection->expects(self::once())->method('commit');
        $connection->expects(self::never())->method('rollBack');

        $repository = new AdministrationConfigRegistryRepository($this->registryWithConnection($connection));

        self::assertSame(
            ['applications' => 0, 'tools' => 0],
            $repository->replace([], []),
        );
    }

    public function testReplacePersistsApplicationAndDeduplicatesToolsByCompositeKey(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('beginTransaction');
        $connection->expects(self::exactly(2))->method('executeStatement');
        $connection->expects(self::once())->method('commit');

        $insertCalls = [];
        $connection->expects(self::exactly(2))
            ->method('insert')
            ->willReturnCallback(static function (string $table, array $data, array $types) use (&$insertCalls): int {
                $insertCalls[] = [$table, $data, $types];

                return 1;
            });

        $application = new AdministrationConfigApplicationDescriptor(
            applicationCode: 'app',
            label: 'Application',
            rootPath: 'D:/apps/app',
            manifestPath: 'config/administering.yaml',
            checksum: 'checksum-app',
        );
        $firstTool = new AdministrationConfigToolDescriptor(
            applicationCode: 'app',
            toolCode: 'cache',
            label: 'Cache',
            description: 'First descriptor',
            metadata: ['source' => 'first'],
            formClass: 'App\\Form\\CacheType',
            serviceClass: 'App\\Service\\CacheService',
            requiredPermission: 'administration.cache',
            editableFields: ['enabled'],
            sensitiveFields: ['token'],
            readableFiles: ['config/cache.yaml'],
            writableFiles: ['config/cache.yaml'],
            secretNames: ['token' => 'CACHE_TOKEN'],
            applyStrategy: 'reload',
        );
        $replacementTool = new AdministrationConfigToolDescriptor(
            applicationCode: 'app',
            toolCode: 'cache',
            label: 'Cache replacement',
            description: 'Replacement descriptor',
            metadata: ['source' => 'replacement'],
            applyStrategy: 'restart',
        );

        $result = (new AdministrationConfigRegistryRepository($this->registryWithConnection($connection)))
            ->replace(['app' => $application], ['app' => [$firstTool, $replacementTool]]);

        self::assertSame(['applications' => 1, 'tools' => 1], $result);
        self::assertCount(2, $insertCalls);
        self::assertSame('administration_config_application', $insertCalls[0][0]);
        self::assertSame('app', $insertCalls[0][1]['application_code']);
        self::assertSame('Application', $insertCalls[0][1]['label']);
        self::assertTrue($insertCalls[0][1]['enabled']);
        self::assertInstanceOf(\DateTimeImmutable::class, $insertCalls[0][1]['discovered_at']);

        self::assertSame('administration_config_tool', $insertCalls[1][0]);
        self::assertSame('app', $insertCalls[1][1]['application_code']);
        self::assertSame('cache', $insertCalls[1][1]['tool_code']);
        self::assertSame('Cache replacement', $insertCalls[1][1]['label']);
        self::assertSame('Replacement descriptor', $insertCalls[1][1]['description']);
        self::assertSame(['source' => 'replacement'], $insertCalls[1][1]['metadata']);
        self::assertSame('restart', $insertCalls[1][1]['apply_strategy']);
        self::assertInstanceOf(\DateTimeImmutable::class, $insertCalls[1][1]['discovered_at']);
    }

    public function testReplaceRollsBackActiveTransactionWhenWriteFails(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('beginTransaction');
        $connection->expects(self::once())
            ->method('executeStatement')
            ->with('DELETE FROM administration_config_tool')
            ->willThrowException(new \RuntimeException('write failed'));
        $connection->expects(self::once())->method('isTransactionActive')->willReturn(true);
        $connection->expects(self::once())->method('rollBack');
        $connection->expects(self::never())->method('commit');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('write failed');

        (new AdministrationConfigRegistryRepository($this->registryWithConnection($connection)))
            ->replace([], []);
    }

    public function testReplaceDoesNotRollbackWhenTransactionIsNoLongerActive(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('beginTransaction');
        $connection->expects(self::once())
            ->method('executeStatement')
            ->willThrowException(new \RuntimeException('write failed'));
        $connection->expects(self::once())->method('isTransactionActive')->willReturn(false);
        $connection->expects(self::never())->method('rollBack');

        $this->expectException(\RuntimeException::class);

        (new AdministrationConfigRegistryRepository($this->registryWithConnection($connection)))
            ->replace([], []);
    }

    public function testReplaceFailsClosedWithoutEntityManager(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->with(AdministrationConfigApplicationEntity::class)->willReturn(null);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No Doctrine entity manager is configured for Administering config registry records.');

        (new AdministrationConfigRegistryRepository($registry))->replace([], []);
    }

    private function registryWithConnection(Connection $connection): ManagerRegistry
    {
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getConnection')->willReturn($connection);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')
            ->with(AdministrationConfigApplicationEntity::class)
            ->willReturn($manager);

        return $registry;
    }
}
