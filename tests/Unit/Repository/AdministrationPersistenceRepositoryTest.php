<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Repository;

use App\Administering\Repository\AdministrationPersistenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

final class AdministrationPersistenceRepositoryTest extends TestCase
{
    public function testPersistPersistsAndFlushesByDefault(): void
    {
        $entity = new AdministrationPersistenceRepositoryProbe();
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::once())->method('persist')->with($entity);
        $manager->expects(self::once())->method('flush');

        $repository = new AdministrationPersistenceRepository($this->registryReturning($manager));
        $repository->persist($entity);
    }

    public function testPersistCanSkipFlush(): void
    {
        $entity = new AdministrationPersistenceRepositoryProbe();
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::once())->method('persist')->with($entity);
        $manager->expects(self::never())->method('flush');

        (new AdministrationPersistenceRepository($this->registryReturning($manager)))->persist($entity, false);
    }

    public function testRemoveRemovesAndFlushesByDefault(): void
    {
        $entity = new AdministrationPersistenceRepositoryProbe();
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::once())->method('remove')->with($entity);
        $manager->expects(self::once())->method('flush');

        (new AdministrationPersistenceRepository($this->registryReturning($manager)))->remove($entity);
    }

    public function testRemoveCanSkipFlush(): void
    {
        $entity = new AdministrationPersistenceRepositoryProbe();
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::once())->method('remove')->with($entity);
        $manager->expects(self::never())->method('flush');

        (new AdministrationPersistenceRepository($this->registryReturning($manager)))->remove($entity, false);
    }

    public function testPersistIfManagedReturnsFalseWithoutEntityManager(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn(null);

        self::assertFalse((new AdministrationPersistenceRepository($registry))->persistIfManaged(new AdministrationPersistenceRepositoryProbe()));
    }

    public function testPersistIfManagedPersistsAndCanSkipFlush(): void
    {
        $entity = new AdministrationPersistenceRepositoryProbe();
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::once())->method('persist')->with($entity);
        $manager->expects(self::never())->method('flush');

        self::assertTrue((new AdministrationPersistenceRepository($this->registryReturning($manager)))->persistIfManaged($entity, false));
    }

    public function testManagerIntrospectionReflectsRegistry(): void
    {
        $manager = $this->createMock(EntityManagerInterface::class);
        $repository = new AdministrationPersistenceRepository($this->registryReturning($manager));

        self::assertTrue($repository->hasManagerFor(AdministrationPersistenceRepositoryProbe::class));
        self::assertSame($manager::class, $repository->managerClassFor(AdministrationPersistenceRepositoryProbe::class));
    }

    public function testManagerIntrospectionReturnsNegativeStateWithoutEntityManager(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn(null);
        $repository = new AdministrationPersistenceRepository($registry);

        self::assertFalse($repository->hasManagerFor(AdministrationPersistenceRepositoryProbe::class));
        self::assertNull($repository->managerClassFor(AdministrationPersistenceRepositoryProbe::class));
    }

    public function testFlushDelegatesToResolvedEntityManager(): void
    {
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::once())->method('flush');

        (new AdministrationPersistenceRepository($this->registryReturning($manager)))->flush(AdministrationPersistenceRepositoryProbe::class);
    }

    public function testPersistAllPersistsEveryEntityAndFlushesOnce(): void
    {
        $first = new AdministrationPersistenceRepositoryProbe();
        $second = new AdministrationPersistenceRepositoryProbe();
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::exactly(2))
            ->method('persist')
            ->with(self::logicalOr(self::identicalTo($first), self::identicalTo($second)));
        $manager->expects(self::once())->method('flush');

        (new AdministrationPersistenceRepository($this->registryReturning($manager)))
            ->persistAll([$first, $second], AdministrationPersistenceRepositoryProbe::class);
    }

    public function testFindDelegatesToEntityManager(): void
    {
        $entity = new AdministrationPersistenceRepositoryProbe();
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::once())
            ->method('find')
            ->with(AdministrationPersistenceRepositoryProbe::class, 42)
            ->willReturn($entity);

        self::assertSame(
            $entity,
            (new AdministrationPersistenceRepository($this->registryReturning($manager)))
                ->find(AdministrationPersistenceRepositoryProbe::class, 42),
        );
    }

    public function testFindOneByIfManagedReturnsNullWithoutEntityManager(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn(null);

        self::assertNull(
            (new AdministrationPersistenceRepository($registry))
                ->findOneByIfManaged(AdministrationPersistenceRepositoryProbe::class, ['id' => 1]),
        );
    }

    public function testFindOneByIfManagedDelegatesToObjectRepository(): void
    {
        $entity = new AdministrationPersistenceRepositoryProbe();
        $objectRepository = $this->createMock(EntityRepository::class);
        $objectRepository->expects(self::once())->method('findOneBy')->with(['id' => 1])->willReturn($entity);
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getRepository')->with(AdministrationPersistenceRepositoryProbe::class)->willReturn($objectRepository);

        self::assertSame(
            $entity,
            (new AdministrationPersistenceRepository($this->registryReturning($manager)))
                ->findOneByIfManaged(AdministrationPersistenceRepositoryProbe::class, ['id' => 1]),
        );
    }

    public function testFindOneByDelegatesToObjectRepository(): void
    {
        $entity = new AdministrationPersistenceRepositoryProbe();
        $objectRepository = $this->createMock(EntityRepository::class);
        $objectRepository->expects(self::once())->method('findOneBy')->with(['name' => 'probe'])->willReturn($entity);
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getRepository')->with(AdministrationPersistenceRepositoryProbe::class)->willReturn($objectRepository);

        self::assertSame(
            $entity,
            (new AdministrationPersistenceRepository($this->registryReturning($manager)))
                ->findOneBy(AdministrationPersistenceRepositoryProbe::class, ['name' => 'probe']),
        );
    }

    public function testFindByDelegatesCriteriaOrderingLimitAndOffset(): void
    {
        $entity = new AdministrationPersistenceRepositoryProbe();
        $objectRepository = $this->createMock(EntityRepository::class);
        $objectRepository->expects(self::once())
            ->method('findBy')
            ->with(['enabled' => true], ['id' => 'DESC'], 5, 10)
            ->willReturn([$entity]);
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getRepository')->with(AdministrationPersistenceRepositoryProbe::class)->willReturn($objectRepository);

        self::assertSame(
            [$entity],
            (new AdministrationPersistenceRepository($this->registryReturning($manager)))
                ->findBy(AdministrationPersistenceRepositoryProbe::class, ['enabled' => true], ['id' => 'DESC'], 5, 10),
        );
    }

    public function testDeleteByBuildsParameterizedDeleteQuery(): void
    {
        $query = $this->createMock(Query::class);
        $query->expects(self::once())->method('execute')->willReturn(2);

        $builder = $this->createMock(QueryBuilder::class);
        $builder->expects(self::once())
            ->method('delete')
            ->with(AdministrationPersistenceRepositoryProbe::class, 'entity')
            ->willReturnSelf();
        $builder->expects(self::exactly(2))
            ->method('andWhere')
            ->with(self::logicalOr(
                self::equalTo('entity.status = :criterion_0'),
                self::equalTo('entity.enabled = :criterion_1'),
            ))
            ->willReturnSelf();
        $builder->expects(self::exactly(2))
            ->method('setParameter')
            ->with(
                self::logicalOr(self::equalTo('criterion_0'), self::equalTo('criterion_1')),
                self::logicalOr(self::equalTo('queued'), self::equalTo(true)),
            )
            ->willReturnSelf();
        $builder->expects(self::once())->method('getQuery')->willReturn($query);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::once())->method('createQueryBuilder')->willReturn($builder);

        self::assertSame(
            2,
            (new AdministrationPersistenceRepository($this->registryReturning($manager)))
                ->deleteBy(AdministrationPersistenceRepositoryProbe::class, ['status' => 'queued', 'enabled' => true]),
        );
    }

    public function testOperationsFailClosedWhenNoEntityManagerExists(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn(null);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No Doctrine entity manager is configured for '.AdministrationPersistenceRepositoryProbe::class.'.');

        (new AdministrationPersistenceRepository($registry))
            ->flush(AdministrationPersistenceRepositoryProbe::class);
    }

    private function registryReturning(EntityManagerInterface $manager): ManagerRegistry
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($manager);

        return $registry;
    }
}

final class AdministrationPersistenceRepositoryProbe
{
}
