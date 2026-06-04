<?php

declare(strict_types=1);

namespace App\Administering\Service\Config;

use App\Administering\Entity\Config\AdministrationConfigTool;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\AbstractType;

final readonly class AdministrationConfigFormResolverService
{
    public function __construct(private ManagerRegistry $managerRegistry)
    {
    }

    public function formClassForTool(string $applicationCode, string $toolCode): ?string
    {
        $tool = $this->tool($applicationCode, $toolCode);
        if (!$tool instanceof AdministrationConfigTool) {
            return null;
        }

        $formClass = $tool->getFormClass();
        if (!class_exists($formClass) || !is_subclass_of($formClass, AbstractType::class)) {
            return null;
        }

        return $formClass;
    }

    private function tool(string $applicationCode, string $toolCode): ?AdministrationConfigTool
    {
        $manager = $this->managerRegistry->getManagerForClass(AdministrationConfigTool::class);
        if (null === $manager) {
            return null;
        }

        $tool = $manager->getRepository(AdministrationConfigTool::class)->findOneBy([
            'applicationCode' => $applicationCode,
            'toolCode' => $toolCode,
        ]);

        return $tool instanceof AdministrationConfigTool ? $tool : null;
    }
}
