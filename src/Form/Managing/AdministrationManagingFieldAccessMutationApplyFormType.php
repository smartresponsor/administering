<?php

declare(strict_types=1);

namespace App\Administering\Form\Managing;

use App\Administering\Value\Form\Managing\AdministrationManagingFieldAccessMutationApplyData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AdministrationManagingFieldAccessMutationApplyFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('requestKey', TextType::class, ['label' => 'Review request key'])
            ->add('submit', SubmitType::class, ['label' => 'Apply Managing field access review']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AdministrationManagingFieldAccessMutationApplyData::class,
            'method' => 'POST',
        ]);
    }
}
