<?php

declare(strict_types=1);

namespace App\Administering\Form\Config;

use App\Administering\Value\Form\Config\AdministeringIntegrationConfigData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AdministeringIntegrationConfigFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('uiLabel', TextType::class, ['label' => 'UI label'])
            ->add('routePrefix', TextType::class, ['label' => 'Route prefix'])
            ->add('rollingExternalAccessBackend', ChoiceType::class, [
                'label' => 'Rolling external access backend',
                'choices' => ['rolling' => 'rolling', 'deny' => 'deny', 'allow' => 'allow'],
            ])
            ->add('rollingExternalAccessFailureEffect', ChoiceType::class, [
                'label' => 'Rolling external access failure effect',
                'choices' => ['deny' => 'deny', 'allow' => 'allow', 'fallback' => 'fallback'],
            ])
            ->add('rollingExternalAccessPermissionKey', TextType::class, ['label' => 'Rolling permission key'])
            ->add('rollingExternalAccessReadinessSurface', TextType::class, ['label' => 'Readiness surface'])
            ->add('profileStorageEntityManager', ChoiceType::class, [
                'label' => 'Profile storage entity manager',
                'choices' => ['system' => 'system', 'postgres' => 'postgres'],
            ])
            ->add('save', SubmitType::class, ['label' => 'Save pending'])
            ->add('apply', SubmitType::class, ['label' => 'Apply now', 'attr' => ['class' => 'btn btn-primary']]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AdministeringIntegrationConfigData::class,
        ]);
    }
}
