<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use App\Administering\Form\Managing\AdministrationFieldViewProfileReviewFormType;
use App\Administering\ServiceInterface\Accessing\AdministrationCurrentUserContextProviderInterface;
use App\Administering\ServiceInterface\Managing\AdministrationFieldViewProfileReviewServiceInterface;
use App\Administering\Support\Form\AdministrationFormInputParser;
use App\Administering\Value\Form\Managing\AdministrationFieldViewProfileReviewData;
use App\Administering\Value\Rolling\AdministrationFieldViewProfileEditRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Review-only surface for Managing field view profile changes.
 */
final class AdministrationManagingFieldViewProfileReviewController extends AbstractController
{
    public function __construct(
        private readonly AdministrationFieldViewProfileReviewServiceInterface $reviewService,
        private readonly AdministrationCurrentUserContextProviderInterface $currentUserContextProvider,
    ) {
    }

    #[Route('/admin/managing/field-view-profiles/edit', name: 'administration_managing_field_view_profile_edit', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.permission_catalog.view', 'administering:managing-field-view-profile');

        $form = $this->createForm(AdministrationFieldViewProfileReviewFormType::class, new AdministrationFieldViewProfileReviewData(), [
            'action' => $this->generateUrl('administration_managing_field_view_profile_review'),
        ]);

        return $this->render('@Administering/administering/form_page.html.twig', [
            'page_title' => 'Managing Field View Profile Review',
            'lead' => 'Review-only surface. It builds a normalized Managing profile payload but does not persist or apply it.',
            'form' => $form->createView(),
            'result_title' => null,
            'result_json' => null,
            'error_message' => null,
            'action_links' => [],
            'back_url' => null,
        ]);
    }

    #[Route('/admin/managing/field-view-profiles/review', name: 'administration_managing_field_view_profile_review', methods: ['POST'])]
    public function review(Request $request): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.permission_catalog.view', 'administering:managing-field-view-profile');

        $form = $this->createForm(AdministrationFieldViewProfileReviewFormType::class, new AdministrationFieldViewProfileReviewData(), [
            'action' => $this->generateUrl('administration_managing_field_view_profile_review'),
        ]);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('@Administering/administering/form_page.html.twig', [
                'page_title' => 'Managing Field View Profile Review',
                'lead' => 'Review-only surface. It builds a normalized Managing profile payload but does not persist or apply it.',
                'form' => $form->createView(),
                'result_title' => null,
                'result_json' => null,
                'error_message' => null,
                'action_links' => [],
                'back_url' => $this->generateUrl('administration_managing_field_view_profile_edit'),
            ]);
        }

        $data = $form->getData();
        $currentUser = $this->currentUserContextProvider->current();
        try {
            $result = $this->reviewService->review(new AdministrationFieldViewProfileEditRequest(
                subjectType: trim($data->subjectType),
                subjectIdentifier: trim($data->subjectIdentifier),
                pageName: trim($data->pageName),
                visibleFields: AdministrationFormInputParser::parseDelimitedList($data->visibleFields),
                hiddenFields: AdministrationFormInputParser::parseDelimitedList($data->hiddenFields),
                resourceClass: trim($data->resourceClass) ?: null,
                reason: trim((string) $data->reason) ?: null,
                mode: trim($data->mode),
                requestedBySubject: $currentUser?->subjectIdentifier() ?? 'administering:anonymous',
            ));
            $errorMessage = null;
        } catch (\InvalidArgumentException $exception) {
            $result = null;
            $errorMessage = $exception->getMessage();
        }

        if (null === $result) {
            return $this->render('@Administering/administering/form_page.html.twig', [
                'page_title' => 'Managing Field View Profile Review',
                'lead' => 'Review-only surface. It builds a normalized Managing profile payload but does not persist or apply it.',
                'form' => $this->createForm(AdministrationFieldViewProfileReviewFormType::class, $data, [
                    'action' => $this->generateUrl('administration_managing_field_view_profile_review'),
                ])->createView(),
                'result_title' => null,
                'result_json' => null,
                'error_message' => $errorMessage,
                'action_links' => [],
                'back_url' => $this->generateUrl('administration_managing_field_view_profile_edit'),
            ]);
        }

        return $this->render('@Administering/administering/form_page.html.twig', [
            'page_title' => 'Managing Field View Profile Review',
            'lead' => 'Review-only surface. It builds a normalized Managing profile payload but does not persist or apply it.',
            'form' => $this->createForm(AdministrationFieldViewProfileReviewFormType::class, $data, [
                'action' => $this->generateUrl('administration_managing_field_view_profile_review'),
            ])->createView(),
            'result_title' => sprintf('Change type: %s', $result->changeType),
            'result_json' => json_encode($result->toSafeArray(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
            'error_message' => null,
            'action_links' => [],
            'back_url' => $this->generateUrl('administration_managing_field_view_profile_edit'),
        ]);
    }
}
