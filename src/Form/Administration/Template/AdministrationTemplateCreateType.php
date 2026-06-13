<?php

declare(strict_types=1);

namespace App\Administering\Form\Administration\Template;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Skeleton form type for the Administration CRUD route-map entry `administration.template.create`.
 */
final class AdministrationTemplateCreateType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
        ]);
    }
}
