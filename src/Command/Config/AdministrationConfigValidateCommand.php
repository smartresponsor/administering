<?php

declare(strict_types=1);

namespace App\Administering\Command\Config;

use App\Administering\Service\Config\AdministrationConfigToolRegistryService;
use App\Administering\ServiceInterface\Config\ConfigToolServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Form\AbstractType;

#[AsCommand(
    name: 'administering:config:validate',
    description: 'Validates discovered configuration tool descriptors, form classes, and secret/file whitelists.',
)]
final class AdministrationConfigValidateCommand extends Command
{
    public function __construct(private readonly AdministrationConfigToolRegistryService $registryService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $descriptors = $this->registryService->toolDescriptors();
        $errors = [];

        foreach ($descriptors as $descriptor) {
            $formClass = $descriptor->formClass;
            $serviceClass = $descriptor->serviceClass;

            if (null === $formClass || !class_exists($formClass) || !is_subclass_of($formClass, AbstractType::class)) {
                $errors[] = sprintf('%s/%s: invalid form class %s', $descriptor->applicationCode, $descriptor->toolCode, $formClass ?? '<missing>');
            }

            if (null === $serviceClass || !class_exists($serviceClass) || !is_subclass_of($serviceClass, ConfigToolServiceInterface::class)) {
                $errors[] = sprintf('%s/%s: missing service class %s', $descriptor->applicationCode, $descriptor->toolCode, $serviceClass ?? '<missing>');
            }

            foreach ($descriptor->writableFiles as $file) {
                if (str_starts_with($file, '/')
                    || str_contains($file, '..')
                    || (!str_starts_with($file, 'config/') && !str_starts_with($file, '.env'))) {
                    $errors[] = sprintf('%s/%s: disallowed writable file %s', $descriptor->applicationCode, $descriptor->toolCode, $file);
                }
            }

            foreach ($descriptor->secretNames as $fieldKey => $secretName) {
                if (!preg_match('/^[A-Z0-9_]+$/', $secretName)) {
                    $errors[] = sprintf('%s/%s: invalid secret mapping %s => %s', $descriptor->applicationCode, $descriptor->toolCode, $fieldKey, $secretName);
                }
            }
        }

        if ([] === $errors) {
            $io->success(sprintf('Validated %d configuration tool descriptors.', count($descriptors)));

            return Command::SUCCESS;
        }

        $io->error($errors);

        return Command::FAILURE;
    }
}
