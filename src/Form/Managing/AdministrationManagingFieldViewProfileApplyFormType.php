<?php

declare(strict_types=1);

namespace App\Administering\Form\Managing;

use App\Administering\Value\Form\Managing\AdministrationManagingFieldViewProfileApplyData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AdministrationManagingFieldViewProfileApplyFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('normalizedProfilePayload', TextareaType::class, ['label' => 'Normalized profile payload JSON'])
            ->add('reviewContext', TextareaType::class, ['label' => 'Review context JSON'])
            ->add('reason', TextareaType::class, ['required' => false, 'label' => 'Reason'])
            ->add('submit', SubmitType::class, ['label' => 'Prepare Managing apply payload']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AdministrationManagingFieldViewProfileApplyData::class,
            'method' => 'POST',
        ]);
    }
}
