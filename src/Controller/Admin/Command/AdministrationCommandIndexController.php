<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Command;

use App\Administering\Service\Admin\AdministrationCommandIndexService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Operator-facing command index backed by src/Command discovery. */
final class AdministrationCommandIndexController extends AbstractController
{
    public function __construct(private readonly AdministrationCommandIndexService $commandIndexService)
    {
    }

    #[Route('/admin/commands/index', name: 'administration_command_index', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('administration.dashboard.view', 'administering:commands');

        return $this->render('@Administering/administering/runtime_source_index.html.twig', [
            'index' => $this->commandIndexService->index(),
            'navigation' => [],
        ]);
    }
}
