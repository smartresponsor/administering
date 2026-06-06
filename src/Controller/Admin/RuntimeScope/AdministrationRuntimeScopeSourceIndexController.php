<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\RuntimeScope;

use App\Administering\Provider\Admin\AdministrationRuntimeSourceNavigationProvider;
use App\Administering\Service\RuntimeScope\AdministrationAppRuntimeScopeIndexService;
use App\Administering\Service\RuntimeScope\AdministrationComposerIndexService;
use App\Administering\Service\RuntimeScope\AdministrationRuntimeLockIndexService;
use App\Administering\Value\RuntimeScope\AdministrationRuntimeSourceIndex;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Local runtime-scope source pages backed only by Administering evidence readers.
 */
final class AdministrationRuntimeScopeSourceIndexController extends AbstractController
{
    public function __construct(
        private readonly AdministrationComposerIndexService $composerIndexService,
        private readonly AdministrationAppRuntimeScopeIndexService $appRuntimeScopeIndexService,
        private readonly AdministrationRuntimeLockIndexService $runtimeLockIndexService,
        private readonly AdministrationRuntimeSourceNavigationProvider $navigationProvider,
    ) {
    }

    #[Route('/admin/runtime-scope/composer/index', name: 'administration_composer_index', methods: ['GET'])]
    public function composer(Request $request): Response
    {
        $this->denyAccessUnlessGranted('administration.dashboard.view', 'administering:runtime_scope');

        return $this->renderIndex($this->composerIndexService->index($this->hostDir($request)));
    }

    #[Route('/admin/runtime-scope/app-runtime-scope/index', name: 'administration_runtime_scope_index', methods: ['GET'])]
    public function runtimeScope(): Response
    {
        $this->denyAccessUnlessGranted('administration.dashboard.view', 'administering:runtime_scope');

        return $this->renderIndex($this->appRuntimeScopeIndexService->index());
    }

    #[Route('/admin/runtime-scope/lock/index', name: 'administration_runtime_lock_index', methods: ['GET'])]
    public function runtimeLock(Request $request): Response
    {
        $this->denyAccessUnlessGranted('administration.dashboard.view', 'administering:runtime_scope');

        return $this->renderIndex($this->runtimeLockIndexService->index($this->hostDir($request)));
    }

    private function renderIndex(AdministrationRuntimeSourceIndex $index): Response
    {
        return $this->render('@Administering/administering/runtime_source_index.html.twig', [
            'index' => $index,
            'navigation' => $this->navigationProvider->templateNavigation(),
        ]);
    }

    private function hostDir(Request $request): string
    {
        $hostDir = $request->query->get('hostDir', '');

        return is_string($hostDir) ? $hostDir : '';
    }
}
