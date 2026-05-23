<?php

declare(strict_types=1);

namespace App\Administering\Form\Operation;

use App\Administering\Value\Form\Operation\AdministrationOperationLaunchData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

final class AdministrationOperationLaunchFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('operationType', HiddenType::class, [
                'constraints' => [
                    new NotBlank(message: 'Operation type is required.'),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Queue',
                'attr' => [
                    'class' => 'btn btn-primary btn-sm',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AdministrationOperationLaunchData::class,
            'method' => 'POST',
            'csrf_token_id' => 'administering.operation.start',
        ]);

        $resolver->setAllowedTypes('csrf_token_id', 'string');
    }
}
