<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Managing;

use App\Administering\Form\Managing\AdministrationManagingFieldViewProfileApplyFormType;
use App\Administering\ServiceInterface\Accessing\AdministrationCurrentUserContextProviderInterface;
use App\Administering\ServiceInterface\Managing\AdministrationFieldViewProfileApplyServiceInterface;
use App\Administering\Support\Form\AdministrationFormInputParser;
use App\Administering\Value\Form\Managing\AdministrationManagingFieldViewProfileApplyData;
use App\Administering\Value\Managing\ManagingFieldViewProfileApplyRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Apply-preparation surface for reviewed Managing field view profile payloads.
 */
final class AdministrationManagingFieldViewProfileApplyController extends AbstractController
{
    public function __construct(
        private readonly AdministrationFieldViewProfileApplyServiceInterface $applyService,
        private readonly AdministrationCurrentUserContextProviderInterface $currentUserContextProvider,
    ) {
    }

    #[Route('/ea/managing/field-view-profiles/apply', name: 'administration_managing_field_view_profile_apply', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.permission_catalog.view', 'administering:managing-field-view-profile-apply');

        $form = $this->createForm(AdministrationManagingFieldViewProfileApplyFormType::class, new AdministrationManagingFieldViewProfileApplyData(), [
            'action' => $this->generateUrl('administration_managing_field_view_profile_apply_prepare'),
        ]);

        return $this->render('@Administering/administering/form_page.html.twig', [
            'page_title' => 'Apply Managing Field View Profile Review',
            'lead' => 'This surface validates a reviewed profile payload and prepares a Managing apply-handler payload. It does not write Managing storage directly.',
            'form' => $form->createView(),
            'result_title' => null,
            'result_json' => null,
            'error_message' => null,
            'action_links' => [],
            'back_url' => null,
        ]);
    }

    #[Route('/ea/managing/field-view-profiles/apply/prepare', name: 'administration_managing_field_view_profile_apply_prepare', methods: ['POST'])]
    public function prepare(Request $request): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.permission_catalog.view', 'administering:managing-field-view-profile-apply');

        $form = $this->createForm(AdministrationManagingFieldViewProfileApplyFormType::class, new AdministrationManagingFieldViewProfileApplyData(), [
            'action' => $this->generateUrl('administration_managing_field_view_profile_apply_prepare'),
        ]);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('@Administering/administering/form_page.html.twig', [
                'page_title' => 'Apply Managing Field View Profile Review',
                'lead' => 'This surface validates a reviewed profile payload and prepares a Managing apply-handler payload. It does not write Managing storage directly.',
                'form' => $form->createView(),
                'result_title' => null,
                'result_json' => null,
                'error_message' => null,
                'action_links' => [],
                'back_url' => $this->generateUrl('administration_managing_field_view_profile_apply'),
            ]);
        }

        $data = $form->getData();
        $currentUser = $this->currentUserContextProvider->current();
        try {
            $result = $this->applyService->prepare(new ManagingFieldViewProfileApplyRequest(
                normalizedProfilePayload: AdministrationFormInputParser::parseJsonObject($data->normalizedProfilePayload, 'normalizedProfilePayload'),
                reviewContext: AdministrationFormInputParser::parseJsonObject($data->reviewContext, 'reviewContext'),
                requestedBySubject: $currentUser?->subjectIdentifier() ?? 'administering:anonymous',
                reason: trim((string) $data->reason) ?: null,
            ));
            $errorMessage = null;
        } catch (\InvalidArgumentException $exception) {
            $result = null;
            $errorMessage = $exception->getMessage();
        }

        if (null === $result) {
            return $this->render('@Administering/administering/form_page.html.twig', [
                'page_title' => 'Managing Field View Profile Apply Preparation',
                'lead' => 'This surface validates a reviewed profile payload and prepares a Managing apply-handler payload. It does not write Managing storage directly.',
                'form' => $this->createForm(AdministrationManagingFieldViewProfileApplyFormType::class, $data, [
                    'action' => $this->generateUrl('administration_managing_field_view_profile_apply_prepare'),
                ])->createView(),
                'result_title' => null,
                'result_json' => null,
                'error_message' => $errorMessage,
                'action_links' => [],
                'back_url' => $this->generateUrl('administration_managing_field_view_profile_apply'),
            ]);
        }

        return $this->render('@Administering/administering/form_page.html.twig', [
            'page_title' => 'Managing Field View Profile Apply Preparation',
            'lead' => 'This surface validates a reviewed profile payload and prepares a Managing apply-handler payload. It does not write Managing storage directly.',
            'form' => $this->createForm(AdministrationManagingFieldViewProfileApplyFormType::class, $data, [
                'action' => $this->generateUrl('administration_managing_field_view_profile_apply_prepare'),
            ])->createView(),
            'result_title' => 'Apply preparation result',
            'result_json' => json_encode($result->toSafeArray(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
            'error_message' => null,
            'action_links' => [],
            'back_url' => $this->generateUrl('administration_managing_field_view_profile_apply'),
        ]);
    }
}
