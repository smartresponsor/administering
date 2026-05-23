<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin;

use App\Accessing\Entity\AccessAccountEntity;
use App\Administering\BuilderInterface\Admin\AdministrationMainMenuBuilderInterface;
use App\Administering\ProviderInterface\Admin\AdministrationServiceSectionToolDashboardProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[AdminDashboard(routePath: '/admin', routeName: 'administration_admin_index')]
final class AdministrationDashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly AdministrationMainMenuBuilderInterface $mainMenuBuilder,
        private readonly AdministrationServiceSectionToolDashboardProviderInterface $toolDashboardProvider,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    public function index(): Response
    {
        if (!$this->getUser() instanceof AccessAccountEntity) {
            return $this->redirectToRoute('accessing_sign_in');
        }

        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return parent::index();
    }

    #[Route('/admin/service-section/{sectionKey}/tools', name: 'administration_service_section_tools', methods: ['GET'])]
    public function serviceSectionTools(string $sectionKey): Response
    {
        if (!$this->getUser() instanceof AccessAccountEntity) {
            return $this->redirectToRoute('accessing_sign_in');
        }

        $dashboard = $this->toolDashboardProvider->dashboardForSection($sectionKey);
        if (null === $dashboard) {
            throw $this->createNotFoundException(sprintf('Unknown Administering service section "%s".', $sectionKey));
        }

        $this->denyAccessUnlessGranted($dashboard->section->permission, 'administering:service-section:'.$sectionKey);

        return $this->render('@Administering/easy_admin/service_section_tools.html.twig', [
            'dashboard' => $dashboard,
            'primaryCrudUrl' => $this->adminUrlGenerator
                ->setDashboard(self::class)
                ->setController($dashboard->section->primaryCrudControllerClass)
                ->setAction(Crud::PAGE_INDEX)
                ->generateUrl(),
        ]);
    }

    #[Route('/admin/service-section/{sectionKey}/tools/{toolShortName}', name: 'administration_service_section_tool_detail', methods: ['GET'])]
    public function serviceSectionToolDetail(string $sectionKey, string $toolShortName): Response
    {
        if (!$this->getUser() instanceof AccessAccountEntity) {
            return $this->redirectToRoute('accessing_sign_in');
        }

        $detail = $this->toolDashboardProvider->detailForTool($sectionKey, $toolShortName);
        if (null === $detail) {
            throw $this->createNotFoundException(sprintf('Unknown Administering service tool "%s/%s".', $sectionKey, $toolShortName));
        }

        $this->denyAccessUnlessGranted($detail->section->permission, 'administering:service-section-tool:'.$sectionKey.':'.$toolShortName);

        return $this->render('@Administering/easy_admin/service_section_tool_detail.html.twig', [
            'detail' => $detail,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('Administering');
    }

    public function configureMenuItems(): iterable
    {
        yield from $this->mainMenuBuilder->build();
    }
}
