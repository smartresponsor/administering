<?php

declare(strict_types=1);

namespace App\Administering\Form\Admin;

use App\Administering\Value\Form\Admin\AdministrationAdminServiceToolRuntimeControlsData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;

final class AdministrationAdminServiceToolRuntimeControlsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('enabled', CheckboxType::class, [
                'label' => 'Enabled',
                'required' => false,
                'help' => 'Disabled tools cannot be opened from EasyAdmin.',
            ])
            ->add('visible', CheckboxType::class, [
                'label' => 'Visible',
                'required' => false,
                'help' => 'Invisible tools are hidden from normal admin surfaces.',
            ])
            ->add('position', IntegerType::class, [
                'label' => 'Menu/index position',
                'help' => 'Lower values appear earlier inside the section.',
                'constraints' => [
                    new GreaterThanOrEqual(value: 0, message: 'Position must be zero or greater.'),
                ],
            ])
            ->add('labelOverride', TextType::class, [
                'label' => 'Display label override',
                'required' => false,
                'help' => 'Optional runtime display label stored in SQLite. Leave blank to use the generated label.',
                'constraints' => [
                    new Length(max: 180, maxMessage: 'Display label override must be 180 characters or fewer.'),
                ],
            ])
            ->add('clearLabelOverride', CheckboxType::class, [
                'label' => 'Clear display label override',
                'required' => false,
                'help' => 'Use the generated label from the service class name again.',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Save runtime controls',
                'attr' => [
                    'class' => 'btn btn-primary btn-sm',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AdministrationAdminServiceToolRuntimeControlsData::class,
            'method' => 'POST',
            'csrf_token_id' => 'administering.service_tool.runtime_controls',
        ]);
    }
}
