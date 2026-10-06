<?php

declare(strict_types=1);

namespace App\Administering\Form\Config;

use App\Administering\Mapper\Config\AdministrationConfigVariableFormMapper;
use App\Administering\Value\Config\AdministrationConfigVariable;
use App\Administering\Value\Config\AdministrationConfigVariableType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Generic Administering-owned form builder for producer-declared variables.
 *
 * Producers declare variables through Configuring\AdministrationConfigVariable; they do not
 * need component-specific Symfony FormType classes for ordinary config tools.
 */
final class AdministrationDynamicConfigToolFormType extends AbstractType
{
    /**
     * @param array{variables:list<AdministrationConfigVariable>} $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach ($options['variables'] as $variable) {
            $builder->add(AdministrationConfigVariableFormMapper::fieldName($variable), $this->fieldType($variable), $this->fieldOptions($variable));
        }

        $builder
            ->add('save', SubmitType::class, ['label' => 'Save pending'])
            ->add('apply', SubmitType::class, ['label' => 'Apply now', 'attr' => ['class' => 'btn btn-primary']]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'csrf_protection' => true,
            'variables' => [],
        ]);
        $resolver->setAllowedTypes('variables', 'array');
    }

    private function fieldType(AdministrationConfigVariable $variable): string
    {
        return match ($variable->type) {
            AdministrationConfigVariableType::BOOL => CheckboxType::class,
            AdministrationConfigVariableType::INT => IntegerType::class,
            AdministrationConfigVariableType::FLOAT => NumberType::class,
            AdministrationConfigVariableType::ENUM => ChoiceType::class,
            AdministrationConfigVariableType::LIST, AdministrationConfigVariableType::MAP, AdministrationConfigVariableType::JSON, AdministrationConfigVariableType::YAML => TextareaType::class,
            AdministrationConfigVariableType::SECRET_REF => PasswordType::class,
            default => TextType::class,
        };
    }

    /** @return array<string, mixed> */
    private function fieldOptions(AdministrationConfigVariable $variable): array
    {
        $options = [
            'label' => $variable->label,
            'required' => $variable->required,
            'help' => $this->help($variable),
        ];

        if (AdministrationConfigVariableType::BOOL === $variable->type) {
            $options['required'] = false;
        }

        if (AdministrationConfigVariableType::SECRET_REF === $variable->type) {
            $options['required'] = false;
            $options['always_empty'] = false;
            $options['help'] = trim((string) $options['help'].' Leave blank to keep the current secret reference.');
        }

        if (AdministrationConfigVariableType::ENUM === $variable->type) {
            $choices = $this->choices($variable);
            if ([] !== $choices) {
                $options['choices'] = $choices;
            }
        }

        return $options;
    }

    /** @return array<string, string> */
    private function choices(AdministrationConfigVariable $variable): array
    {
        $rawChoices = $variable->constraints['choices'] ?? $variable->metadata['choices'] ?? [];
        if (!is_array($rawChoices)) {
            return [];
        }

        $choices = [];
        foreach ($rawChoices as $choice) {
            if (is_string($choice) || is_int($choice)) {
                $choices[(string) $choice] = (string) $choice;
            }
        }

        return $choices;
    }

    private function help(AdministrationConfigVariable $variable): string
    {
        $parts = [$variable->storage];
        if (null !== $variable->targetFile && '' !== $variable->targetFile) {
            $parts[] = $variable->targetFile;
        }

        return 'Storage: '.implode(' / ', $parts);
    }
}
