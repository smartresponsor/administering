<?php

declare(strict_types=1);

namespace App\Administering\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Owns generic Doctrine manager access for Administering application roles.
 *
 * Application services consume repository operations from this type instead of
 * resolving Doctrine managers directly.
 */
final readonly class AdministrationPersistenceRepository
{
    public function __construct(private ManagerRegistry $managerRegistry)
    {
    }

    public function persist(object $entity, bool $flush = true): void
    {
        $manager = $this->entityManagerFor($entity::class);
        $manager->persist($entity);

        if ($flush) {
            $manager->flush();
        }
    }

    public function remove(object $entity, bool $flush = true): void
    {
        $manager = $this->entityManagerFor($entity::class);
        $manager->remove($entity);

        if ($flush) {
            $manager->flush();
        }
    }

    public function persistIfManaged(object $entity, bool $flush = true): bool
    {
        $manager = $this->managerRegistry->getManagerForClass($entity::class);
        if (!$manager instanceof EntityManagerInterface) {
            return false;
        }

        $manager->persist($entity);
        if ($flush) {
            $manager->flush();
        }

        return true;
    }

    /** @param class-string<object> $entityClass */
    public function hasManagerFor(string $entityClass): bool
    {
        return $this->managerRegistry->getManagerForClass($entityClass) instanceof EntityManagerInterface;
    }

    /** @param class-string<object> $entityClass */
    public function managerClassFor(string $entityClass): ?string
    {
        $manager = $this->managerRegistry->getManagerForClass($entityClass);

        return $manager instanceof EntityManagerInterface ? $manager::class : null;
    }

    /** @param class-string<object> $entityClass */
    public function flush(string $entityClass): void
    {
        $this->entityManagerFor($entityClass)->flush();
    }

    /** @param iterable<object> $entities */
    public function persistAll(iterable $entities, string $entityClass): void
    {
        $manager = $this->entityManagerFor($entityClass);
        foreach ($entities as $entity) {
            $manager->persist($entity);
        }

        $manager->flush();
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $entityClass
     *
     * @return T|null
     */
    public function find(string $entityClass, mixed $id): ?object
    {
        return $this->entityManagerFor($entityClass)->find($entityClass, $id);
    }

    /**
     * @template T of object
     *
     * @param class-string<T>      $entityClass
     * @param array<string, mixed> $criteria
     *
     * @return T|null
     */
    public function findOneByIfManaged(string $entityClass, array $criteria): ?object
    {
        $manager = $this->managerRegistry->getManagerForClass($entityClass);
        if (!$manager instanceof EntityManagerInterface) {
            return null;
        }

        return $manager->getRepository($entityClass)->findOneBy($criteria);
    }

    /**
     * @template T of object
     *
     * @param class-string<T>      $entityClass
     * @param array<string, mixed> $criteria
     *
     * @return T|null
     */
    public function findOneBy(string $entityClass, array $criteria): ?object
    {
        return $this->entityManagerFor($entityClass)
            ->getRepository($entityClass)
            ->findOneBy($criteria);
    }

    /**
     * @template T of object
     *
     * @param class-string<T>             $entityClass
     * @param array<string, mixed>        $criteria
     * @param array<string, 'ASC'|'DESC'> $orderBy
     *
     * @return list<T>
     */
    public function findBy(string $entityClass, array $criteria, array $orderBy = [], ?int $limit = null, ?int $offset = null): array
    {
        return $this->entityManagerFor($entityClass)
            ->getRepository($entityClass)
            ->findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * @param class-string<object> $entityClass
     * @param array<string, mixed> $criteria
     */
    public function deleteBy(string $entityClass, array $criteria): int
    {
        $manager = $this->entityManagerFor($entityClass);
        $builder = $manager->createQueryBuilder()->delete($entityClass, 'entity');
        $index = 0;

        foreach ($criteria as $field => $value) {
            $parameter = 'criterion_'.$index++;
            $builder
                ->andWhere(sprintf('entity.%s = :%s', $field, $parameter))
                ->setParameter($parameter, $value);
        }

        return $builder->getQuery()->execute();
    }

    /** @param class-string<object> $entityClass */
    private function entityManagerFor(string $entityClass): EntityManagerInterface
    {
        $manager = $this->managerRegistry->getManagerForClass($entityClass);
        if (!$manager instanceof EntityManagerInterface) {
            throw new \LogicException(sprintf('No Doctrine entity manager is configured for %s.', $entityClass));
        }

        return $manager;
    }
}
