<?php

declare(strict_types=1);

namespace App\Administering\Form\Rolling;

use App\Administering\Value\Form\Rolling\AdministrationRollingSubjectAccessReportLookupData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AdministrationRollingSubjectAccessReportLookupFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('subjectIdentifier', TextType::class, ['label' => 'Subject'])
            ->add('scope', TextType::class, ['label' => 'Scope'])
            ->add('format', ChoiceType::class, [
                'choices' => [
                    'HTML' => 'html',
                    'JSON' => 'json',
                ],
            ])
            ->add('submit', SubmitType::class, ['label' => 'Inspect']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AdministrationRollingSubjectAccessReportLookupData::class,
            'method' => 'GET',
        ]);
    }
}
