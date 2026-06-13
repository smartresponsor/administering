<?php

declare(strict_types=1);

namespace App\Administering\Form\Administration\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Skeleton form type for the Administration CRUD route-map entry `administration.form.export`.
 */
final class AdministrationFormExportType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
        ]);
    }
}
