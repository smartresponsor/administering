<?php

declare(strict_types=1);

namespace App\Administering\Form\RuntimeScope;

use App\Administering\Value\Form\RuntimeScope\AdministrationRuntimeScopeComponentDecisionData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

final class AdministrationRuntimeScopeComponentDecisionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('componentKey', TextType::class, [
                'label' => 'Component',
                'attr' => [
                    'readonly' => true,
                ],
                'constraints' => [
                    new NotBlank(message: 'Component key is required.'),
                    new Regex(pattern: '/^[a-z][a-z0-9-]*$/', message: 'Component key must be a normalized runtime-scope token.'),
                ],
            ])
            ->add('environment', ChoiceType::class, [
                'label' => 'Runtime environment',
                'choices' => [
                    'dev' => 'dev',
                    'prod' => 'prod',
                ],
                'constraints' => [
                    new Choice(choices: ['dev', 'prod'], message: 'Choose dev or prod runtime environment.'),
                ],
            ])
            ->add('enabled', CheckboxType::class, [
                'label' => 'Enabled in selected runtime scope',
                'required' => false,
                'help' => 'Unchecked means the component is explicitly disabled in the selected runtime lock.',
            ])
            ->add('reason', TextareaType::class, [
                'label' => 'Decision note',
                'required' => false,
                'help' => 'Short operator note stored in the audit event. Do not enter secrets.',
                'constraints' => [
                    new Length(max: 500, maxMessage: 'Decision note must be 500 characters or fewer.'),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Save component decision',
                'attr' => [
                    'class' => 'btn btn-primary btn-sm',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AdministrationRuntimeScopeComponentDecisionData::class,
            'method' => 'POST',
            'csrf_token_id' => 'administering.runtime_scope.component_decision',
        ]);
    }
}
