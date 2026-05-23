<?php

declare(strict_types=1);

namespace App\Administering\Form\Accessing;

use App\Administering\Value\Form\Accessing\AdministrationAccessingAccountActionData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

final class AdministrationAccessingAccountActionFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('action', HiddenType::class)
            ->add('accountReference', TextType::class, [
                'label' => 'Account reference',
                'attr' => [
                    'placeholder' => 'accessing:account:123',
                ],
                'constraints' => [
                    new NotBlank(message: 'Account reference is required.'),
                ],
            ])
            ->add('reason', TextareaType::class, [
                'label' => 'Reason',
                'required' => $options['requires_reason'],
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'safe reason',
                ],
                'help' => $options['requires_reason'] ? 'A reason is required for this action.' : 'Optional for this action.',
                'constraints' => $options['requires_reason'] ? [
                    new NotBlank(message: 'Reason is required for this action.'),
                ] : [],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Submit controlled request',
                'attr' => [
                    'class' => 'btn btn-primary btn-sm',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AdministrationAccessingAccountActionData::class,
            'method' => 'POST',
            'requires_reason' => true,
            'csrf_token_id' => 'administering.accessing.account_action',
        ]);

        $resolver->setAllowedTypes('requires_reason', 'bool');
        $resolver->setAllowedTypes('csrf_token_id', 'string');
    }
}
