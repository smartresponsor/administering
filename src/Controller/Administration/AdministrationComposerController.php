<?php

declare(strict_types=1);

namespace App\Administering\Controller\Administration;

use App\Accessing\Entity\AccessAccountEntity;
use App\Administering\Service\RuntimeScope\AdministrationAppRuntimeScopeIndexService;
use App\Administering\Service\RuntimeScope\AdministrationComposerIndexService;
use App\Administering\Service\RuntimeScope\AdministrationRuntimeLockIndexService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdministrationComposerController extends AbstractController
{
    public function __construct(
        private readonly AdministrationComposerIndexService $composerIndexService,
        private readonly AdministrationAppRuntimeScopeIndexService $appRuntimeScopeIndexService,
        private readonly AdministrationRuntimeLockIndexService $runtimeLockIndexService,
    ) {
    }

    #[Route('/administration/composer/index', name: 'administration_composer_index', methods: ['GET'])]
    public function composerIndex(Request $request): Response
    {
        $this->assertAdministrationAccess();

        return $this->renderIndex($this->composerIndexService->index($this->hostDir($request)));
    }

    #[Route('/administration/runtime-scope/index', name: 'administration_runtime_scope_index', methods: ['GET'])]
    public function runtimeScopeIndex(): Response
    {
        $this->assertAdministrationAccess();

        return $this->renderIndex($this->appRuntimeScopeIndexService->index());
    }

    #[Route('/administration/runtime-lock/index', name: 'administration_runtime_lock_index', methods: ['GET'])]
    public function runtimeLockIndex(Request $request): Response
    {
        $this->assertAdministrationAccess();

        return $this->renderIndex($this->runtimeLockIndexService->index($this->hostDir($request)));
    }

    private function renderIndex(\App\Administering\Value\RuntimeScope\AdministrationRuntimeSourceIndex $index): Response
    {
        $response = $this->render('@Administering/administering/runtime_source_index.html.twig', [
            'index' => $index,
            'navigation' => [
                ['label' => 'Composer inventory', 'route' => 'administration_composer_index'],
                ['label' => 'APP_RUNTIME_SCOPE', 'route' => 'administration_runtime_scope_index'],
                ['label' => 'Runtime locks', 'route' => 'administration_runtime_lock_index'],
            ],
        ]);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private', true);
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }

    private function assertAdministrationAccess(): void
    {
        if (!$this->getUser() instanceof AccessAccountEntity) {
            throw $this->createAccessDeniedException('Authentication is required for Administration runtime source indexes.');
        }

        $this->denyAccessUnlessGranted('ROLE_ADMIN');
    }

    private function hostDir(Request $request): string
    {
        $hostDir = $request->query->get('hostDir');

        return is_string($hostDir) ? $hostDir : '';
    }
}
