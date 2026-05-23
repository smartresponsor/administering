<?php

declare(strict_types=1);

namespace App\Administering\Form\Rolling;

use App\Administering\Value\Form\Rolling\AdministrationRollingAclMutationReviewData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AdministrationRollingAclMutationReviewFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('mutationType', ChoiceType::class, [
                'choices' => [
                    'permission.grant' => 'permission.grant',
                    'permission.revoke' => 'permission.revoke',
                    'acl.allow' => 'acl.allow',
                    'acl.deny' => 'acl.deny',
                ],
            ])
            ->add('subjectIdentifier', TextType::class, ['label' => 'Subject identifier'])
            ->add('permissionOrRoleKey', TextType::class, ['label' => 'Permission or role key'])
            ->add('componentKey', TextType::class, ['label' => 'Component key'])
            ->add('resourceClass', TextType::class, ['label' => 'Resource class'])
            ->add('pageName', TextType::class, ['label' => 'Page'])
            ->add('fieldName', TextType::class, ['label' => 'Field'])
            ->add('submit', SubmitType::class, ['label' => 'Review dry-run plan']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AdministrationRollingAclMutationReviewData::class,
            'method' => 'POST',
        ]);
    }
}
