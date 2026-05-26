<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use App\Administering\Form\Managing\AdministrationManagingFieldVisibilityInspectionPrepareFormType;
use App\Administering\ServiceInterface\Accessing\AdministrationCurrentUserContextProviderInterface;
use App\Administering\ServiceInterface\Managing\AdministrationFieldVisibilityInspectionPrepareServiceInterface;
use App\Administering\Support\Form\AdministrationFormInputParser;
use App\Administering\Value\Form\Managing\AdministrationManagingFieldVisibilityInspectionPrepareData;
use App\Managing\Value\Administration\ManagingFieldVisibilityInspectionPrepareRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Prepares read-only Managing field visibility inspection requests from Administering.
 */
final class AdministrationManagingFieldVisibilityInspectionPrepareController extends AbstractController
{
    public function __construct(
        private readonly AdministrationFieldVisibilityInspectionPrepareServiceInterface $prepareService,
        private readonly AdministrationCurrentUserContextProviderInterface $currentUserContextProvider,
    ) {
    }

    #[Route('/admin/managing/field-visibility-inspection', name: 'administration_managing_field_visibility_inspection', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.permission_catalog.view', 'administering:managing-field-visibility-inspection');

        $form = $this->createForm(AdministrationManagingFieldVisibilityInspectionPrepareFormType::class, new AdministrationManagingFieldVisibilityInspectionPrepareData(), [
            'action' => $this->generateUrl('administration_managing_field_visibility_inspection_prepare'),
        ]);

        return $this->render('@Administering/administering/form_page.html.twig', [
            'page_title' => 'Managing Field Visibility Inspection',
            'lead' => 'Prepares a read-only Managing diagnostic request. Administering does not execute Managing runtime here.',
            'form' => $form->createView(),
            'result_title' => null,
            'result_json' => null,
            'error_message' => null,
            'action_links' => [],
            'back_url' => null,
        ]);
    }

    #[Route('/admin/managing/field-visibility-inspection/prepare', name: 'administration_managing_field_visibility_inspection_prepare', methods: ['POST'])]
    public function prepare(Request $request): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.permission_catalog.view', 'administering:managing-field-visibility-inspection');

        $form = $this->createForm(AdministrationManagingFieldVisibilityInspectionPrepareFormType::class, new AdministrationManagingFieldVisibilityInspectionPrepareData(), [
            'action' => $this->generateUrl('administration_managing_field_visibility_inspection_prepare'),
        ]);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('@Administering/administering/form_page.html.twig', [
                'page_title' => 'Managing Field Visibility Inspection',
                'lead' => 'Prepares a read-only Managing diagnostic request. Administering does not execute Managing runtime here.',
                'form' => $form->createView(),
                'result_title' => null,
                'result_json' => null,
                'error_message' => null,
                'action_links' => [],
                'back_url' => $this->generateUrl('administration_managing_field_visibility_inspection'),
            ]);
        }

        $data = $form->getData();
        $currentUser = $this->currentUserContextProvider->current();
        try {
            $result = $this->prepareService->prepare(new ManagingFieldVisibilityInspectionPrepareRequest(
                resourceClass: trim($data->resourceClass),
                fieldName: trim($data->fieldName),
                pageName: trim($data->pageName),
                subjectIdentifier: trim($data->subjectIdentifier) ?: null,
                statusCandidates: AdministrationFormInputParser::parseDelimitedList($data->statusCandidates),
                publicationFlagCandidates: AdministrationFormInputParser::parseDelimitedList($data->publicationFlagCandidates),
                publicationDateCandidates: AdministrationFormInputParser::parseDelimitedList($data->publicationDateCandidates),
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
                'page_title' => 'Prepared Managing Field Visibility Inspection',
                'lead' => 'Prepares a read-only Managing diagnostic request. Administering does not execute Managing runtime here.',
                'form' => $this->createForm(AdministrationManagingFieldVisibilityInspectionPrepareFormType::class, $data, [
                    'action' => $this->generateUrl('administration_managing_field_visibility_inspection_prepare'),
                ])->createView(),
                'result_title' => null,
                'result_json' => null,
                'error_message' => $errorMessage,
                'action_links' => [],
                'back_url' => $this->generateUrl('administration_managing_field_visibility_inspection'),
            ]);
        }

        return $this->render('@Administering/administering/form_page.html.twig', [
            'page_title' => 'Prepared Managing Field Visibility Inspection',
            'lead' => 'Prepares a read-only Managing diagnostic request. Administering does not execute Managing runtime here.',
            'form' => $this->createForm(AdministrationManagingFieldVisibilityInspectionPrepareFormType::class, $data, [
                'action' => $this->generateUrl('administration_managing_field_visibility_inspection_prepare'),
            ])->createView(),
            'result_title' => 'Inspection preparation result',
            'result_json' => json_encode($result->toSafeArray(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
            'error_message' => null,
            'action_links' => [],
            'back_url' => $this->generateUrl('administration_managing_field_visibility_inspection'),
        ]);
    }
}
