<?php

declare(strict_types=1);

namespace App\Administering\Form\Managing;

use App\Administering\Value\Form\Managing\AdministrationFieldViewProfileReviewData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AdministrationFieldViewProfileReviewFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('subjectType', ChoiceType::class, [
                'choices' => [
                    'User' => 'user',
                    'Role' => 'role',
                    'Group' => 'group',
                ],
            ])
            ->add('subjectIdentifier', TextType::class, ['label' => 'Subject identifier'])
            ->add('mode', ChoiceType::class, [
                'choices' => [
                    'Replace' => 'replace',
                    'Merge' => 'merge',
                    'Clear' => 'clear',
                ],
            ])
            ->add('resourceClass', TextType::class, ['required' => false, 'label' => 'Resource class'])
            ->add('pageName', ChoiceType::class, [
                'choices' => [
                    'Index' => 'index',
                    'Detail' => 'detail',
                    'New' => 'new',
                    'Edit' => 'edit',
                    'All' => 'all',
                    'Default' => '*',
                ],
            ])
            ->add('visibleFields', TextareaType::class, ['required' => false, 'label' => 'Visible fields'])
            ->add('hiddenFields', TextareaType::class, ['required' => false, 'label' => 'Hidden fields'])
            ->add('reason', TextType::class, ['required' => false, 'label' => 'Reason'])
            ->add('submit', SubmitType::class, ['label' => 'Review field view profile change']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AdministrationFieldViewProfileReviewData::class,
            'method' => 'POST',
        ]);
    }
}
