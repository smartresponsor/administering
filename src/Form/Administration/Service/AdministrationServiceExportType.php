<?php

declare(strict_types=1);

namespace App\Administering\Form\Administration\Service;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Skeleton form type for the Administration CRUD route-map entry `administration.service.export`.
 */
final class AdministrationServiceExportType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
        ]);
    }
}
