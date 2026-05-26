<?php

declare(strict_types=1);

namespace App\Administering\Form\Symfony;

use App\Administering\Value\Form\Symfony\AdministrationSymfonyRouteRecordSyncData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AdministrationSymfonyRouteRecordSyncFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('submit', SubmitType::class, ['label' => 'Synchronize']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AdministrationSymfonyRouteRecordSyncData::class,
            'method' => 'POST',
        ]);
    }
}
