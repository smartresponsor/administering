<?php

declare(strict_types=1);

namespace App\Administering\Form\Operation;

use App\Administering\Value\Form\Operation\AdministrationOperationServiceSectionAnchorSyncData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AdministrationOperationServiceSectionAnchorSyncFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('section', TextType::class, [
                'label' => 'Section to synchronize',
                'required' => false,
                'help' => 'Leave empty to synchronize every supported section anchor.',
            ])
            ->add('submit', SubmitType::class, ['label' => 'Synchronize anchors']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AdministrationOperationServiceSectionAnchorSyncData::class,
            'method' => 'POST',
        ]);
    }
}
