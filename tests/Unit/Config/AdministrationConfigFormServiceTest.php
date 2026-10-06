<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Config;

use App\Administering\Entity\Config\AdministrationConfigToolEntity;
use App\Administering\Repository\AdministrationPersistenceRepository;
use App\Administering\Service\Config\AdministrationConfigFormService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\AbstractType;

final class AdministrationConfigFormServiceTest extends TestCase
{
    public function testRejectsUnregisteredFormClass(): void
    {
        /** @var EntityRepository&MockObject $repository */
        /** @var EntityRepository<AdministrationConfigToolEntity>&MockObject $repository */
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('findOneBy')
            ->willReturn(new AdministrationConfigToolEntity(
                'Demo',
                'demo.tool',
                'Demo tool',
                'Demo\\MissingFormType',
                self::class,
                'administration.config.view',
            ));

        /** @var EntityManagerInterface&MockObject $manager */
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::once())->method('getRepository')->willReturn($repository);

        /** @var ManagerRegistry&MockObject $registry */
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects(self::once())->method('getManagerForClass')->willReturn($manager);

        $service = new AdministrationConfigFormService(new AdministrationPersistenceRepository($registry));

        self::assertNull($service->formClassForTool('Demo', 'demo.tool'));
    }

    public function testAcceptsConcreteFormClass(): void
    {
        /** @var EntityRepository&MockObject $repository */
        /** @var EntityRepository<AdministrationConfigToolEntity>&MockObject $repository */
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('findOneBy')
            ->willReturn(new AdministrationConfigToolEntity(
                'Demo',
                'demo.tool',
                'Demo tool',
                DemoConfigFormType::class,
                self::class,
                'administration.config.view',
            ));

        /** @var EntityManagerInterface&MockObject $manager */
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::once())->method('getRepository')->willReturn($repository);

        /** @var ManagerRegistry&MockObject $registry */
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects(self::once())->method('getManagerForClass')->willReturn($manager);

        $service = new AdministrationConfigFormService(new AdministrationPersistenceRepository($registry));

        self::assertSame(DemoConfigFormType::class, $service->formClassForTool('Demo', 'demo.tool'));
    }
}

final class DemoConfigFormType extends AbstractType
{
}
