<?php

declare(strict_types=1);

namespace App\Administering\Form\Config;

use App\Administering\Value\Form\Config\AdministeringCredentialConfigData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AdministeringCredentialConfigFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('appSecretReplacement', PasswordType::class, [
                'label' => 'APP_SECRET replacement',
                'required' => false,
                'help' => 'Leave blank to keep the current secret.',
            ])
            ->add('administrationDatabaseUrlReplacement', PasswordType::class, [
                'label' => 'ADMINISTERING_DATABASE_URL replacement',
                'required' => false,
                'help' => 'Leave blank to keep the current secret.',
            ])
            ->add('save', SubmitType::class, ['label' => 'Save pending'])
            ->add('apply', SubmitType::class, ['label' => 'Apply now', 'attr' => ['class' => 'btn btn-primary']]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AdministeringCredentialConfigData::class,
        ]);
    }
}
