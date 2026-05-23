<?php

declare(strict_types=1);

namespace App\Administering\Form\Managing;

use App\Administering\Value\Form\Managing\AdministrationFieldVisibilityInspectionPrepareData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AdministrationFieldVisibilityInspectionPrepareFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('resourceClass', TextType::class, ['label' => 'Resource class'])
            ->add('fieldName', TextType::class, ['label' => 'Field name'])
            ->add('pageName', ChoiceType::class, [
                'choices' => [
                    'Index' => 'index',
                    'Detail' => 'detail',
                    'New' => 'new',
                    'Edit' => 'edit',
                ],
            ])
            ->add('subjectIdentifier', TextType::class, ['required' => false, 'label' => 'Subject identifier'])
            ->add('statusCandidates', TextareaType::class, ['required' => false, 'label' => 'Status candidates'])
            ->add('publicationFlagCandidates', TextareaType::class, ['required' => false, 'label' => 'Publication flag candidates'])
            ->add('publicationDateCandidates', TextareaType::class, ['required' => false, 'label' => 'Publication date candidates'])
            ->add('reason', TextType::class, ['required' => false, 'label' => 'Reason'])
            ->add('submit', SubmitType::class, ['label' => 'Prepare inspection request']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AdministrationFieldVisibilityInspectionPrepareData::class,
            'method' => 'POST',
        ]);
    }
}
