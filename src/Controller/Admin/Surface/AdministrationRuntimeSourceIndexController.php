<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use App\Accessing\Entity\AccessAccountEntity;
use App\Administering\Provider\Admin\AdministrationRuntimeSourceNavigationProvider;
use App\Administering\Service\RuntimeScope\AdministrationAppRuntimeScopeIndexService;
use App\Administering\Service\RuntimeScope\AdministrationComposerIndexService;
use App\Administering\Service\RuntimeScope\AdministrationRuntimeLockIndexService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdministrationRuntimeSourceIndexController extends AbstractController
{
    public function __construct(
        private readonly AdministrationComposerIndexService $composerIndexService,
        private readonly AdministrationAppRuntimeScopeIndexService $appRuntimeScopeIndexService,
        private readonly AdministrationRuntimeLockIndexService $runtimeLockIndexService,
        private readonly AdministrationRuntimeSourceNavigationProvider $runtimeSourceNavigationProvider,
    ) {
    }

    #[Route('/admin/runtime/scope/composer/index', name: 'administration_composer_index', methods: ['GET'], defaults: [
        '_easyadmin_route' => true,
    ])]
    public function composerIndex(Request $request): Response
    {
        $this->assertAdministrationAccess();

        return $this->renderIndex($this->composerIndexService->index($this->hostDir($request)));
    }

    #[Route('/admin/runtime/scope/app/index', name: 'administration_runtime_scope_index', methods: ['GET'], defaults: [
        '_easyadmin_route' => true,
    ])]
    public function runtimeScopeIndex(): Response
    {
        $this->assertAdministrationAccess();

        return $this->renderIndex($this->appRuntimeScopeIndexService->index());
    }

    #[Route('/admin/runtime/scope/lock/index', name: 'administration_runtime_lock_index', methods: ['GET'], defaults: [
        '_easyadmin_route' => true,
    ])]
    public function runtimeLockIndex(Request $request): Response
    {
        $this->assertAdministrationAccess();

        return $this->renderIndex($this->runtimeLockIndexService->index($this->hostDir($request)));
    }

    private function renderIndex(\App\Administering\Value\RuntimeScope\AdministrationRuntimeSourceIndex $index): Response
    {
        $response = $this->render('@Administering/administering/runtime_source_index.html.twig', [
            'index' => $index,
            'navigation' => $this->runtimeSourceNavigationProvider->templateNavigation(),
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
