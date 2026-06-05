<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Crud;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

trait AdministrationRollingCrudActionSupportTrait
{
    protected function rollingEntityManager(string $entityFqcn): EntityManagerInterface
    {
        $manager = $this->container->get('doctrine')->getManagerForClass($entityFqcn);
        if (!$manager instanceof EntityManagerInterface) {
            throw new \RuntimeException(sprintf('No Doctrine entity manager found for "%s".', $entityFqcn));
        }

        return $manager;
    }

    protected function rollingManagedEntity(AdminContext $context, string $expectedClass): object
    {
        $entity = $context->getEntity()->getInstance();
        if (!$entity instanceof $expectedClass) {
            throw new \RuntimeException(sprintf('Expected "%s" in the current admin context, got "%s".', $expectedClass, is_object($entity) ? $entity::class : gettype($entity)));
        }

        return $entity;
    }

    protected function rollingRedirectToIndex(AdminContext $context): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        return $this->redirect($this->container->get(AdminUrlGenerator::class)
            ->setController($context->getCrud()->getControllerFqcn())
            ->setAction('index')
            ->generateUrl());
    }

    protected function rollingPersistAndRedirect(AdminContext $context, object $entity, string $flashMessage): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $manager = $this->rollingEntityManager($entity::class);
        $manager->persist($entity);
        $manager->flush();

        $this->addFlash('success', $flashMessage);

        return $this->rollingRedirectToIndex($context);
    }

    protected function rollingRemoveAndRedirect(AdminContext $context, object $entity, string $flashMessage): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $manager = $this->rollingEntityManager($entity::class);
        $manager->remove($entity);
        $manager->flush();

        $this->addFlash('success', $flashMessage);

        return $this->rollingRedirectToIndex($context);
    }

    /**
     * @param class-string $expectedClass
     */
    protected function rollingBatchMutate(AdminContext $context, BatchActionDto $batchActionDto, string $expectedClass, callable $mutator, string $flashMessagePattern): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $manager = $this->rollingEntityManager($batchActionDto->getEntityFqcn());
        $repository = $manager->getRepository($batchActionDto->getEntityFqcn());
        $changed = 0;

        foreach ($batchActionDto->getEntityIds() as $entityId) {
            $entity = $repository->find($entityId);
            if (!$entity instanceof $expectedClass) {
                continue;
            }

            $before = clone $entity;
            $mutator($entity);
            if ($before != $entity) {
                ++$changed;
            }
        }

        $manager->flush();

        $this->addFlash('success', sprintf($flashMessagePattern, $changed));

        return $this->rollingRedirectToIndex($context);
    }

    /**
     * @param class-string $expectedClass
     */
    protected function rollingBatchRemove(AdminContext $context, BatchActionDto $batchActionDto, string $expectedClass, string $flashMessagePattern): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $manager = $this->rollingEntityManager($batchActionDto->getEntityFqcn());
        $repository = $manager->getRepository($batchActionDto->getEntityFqcn());
        $removed = 0;

        foreach ($batchActionDto->getEntityIds() as $entityId) {
            $entity = $repository->find($entityId);
            if (!$entity instanceof $expectedClass) {
                continue;
            }

            $manager->remove($entity);
            ++$removed;
        }

        $manager->flush();

        $this->addFlash('success', sprintf($flashMessagePattern, $removed));

        return $this->rollingRedirectToIndex($context);
    }
}
