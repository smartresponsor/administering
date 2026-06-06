<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Crud;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

/**
 * Base class for system/admin evidence CRUD screens.
 *
 * State-changing administration flows must go through dedicated reviewed surfaces
 * instead of direct EasyAdmin entity mutation.
 */
/**
 * @extends AbstractCrudController<object>
 */
abstract class AbstractReadOnlyAdministrationCrudController extends AbstractCrudController
{
    abstract protected function entityPermission(): string;

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setEntityPermission($this->entityPermission());
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::NEW, Action::EDIT, Action::DELETE);
    }
}
