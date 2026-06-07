<?php

declare(strict_types=1);

namespace App\Administering\Form\Managing;

use App\Administering\Value\Form\Managing\AdministrationManagingFieldAccessMutationReviewData;
use App\Administering\Value\Form\Managing\AdministrationManagingFieldPermissionVocabulary;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AdministrationManagingFieldAccessMutationReviewFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('subjectType', ChoiceType::class, [
                'choices' => [
                    'Role' => 'role',
                    'User' => 'user',
                    'Group' => 'group',
                ],
            ])
            ->add('subjectIdentifier', TextType::class, ['label' => 'Subject identifier'])
            ->add('effect', ChoiceType::class, [
                'choices' => [
                    'Allow' => 'allow',
                    'Deny' => 'deny',
                ],
            ])
            ->add('permissionKey', ChoiceType::class, [
                'choices' => array_combine(
                    AdministrationManagingFieldPermissionVocabulary::policyKeys(),
                    AdministrationManagingFieldPermissionVocabulary::policyKeys(),
                ),
            ])
            ->add('resourceClass', TextType::class, ['label' => 'Resource class'])
            ->add('fieldName', TextType::class, ['label' => 'Field name'])
            ->add('pageName', TextType::class, ['label' => 'Page name'])
            ->add('operation', TextType::class, ['label' => 'Operation'])
            ->add('reason', TextType::class, ['required' => false, 'label' => 'Reason'])
            ->add('submit', SubmitType::class, ['label' => 'Review field access mutation']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AdministrationManagingFieldAccessMutationReviewData::class,
            'method' => 'POST',
        ]);
    }
}
