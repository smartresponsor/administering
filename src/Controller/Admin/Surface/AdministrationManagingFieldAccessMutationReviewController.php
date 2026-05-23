<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use App\Administering\Form\Managing\AdministrationFieldAccessMutationReviewFormType;
use App\Administering\ServiceInterface\Accessing\AdministrationCurrentUserContextProviderInterface;
use App\Administering\ServiceInterface\Managing\AdministrationFieldAccessMutationReviewServiceInterface;
use App\Administering\Value\Form\Managing\AdministrationFieldAccessMutationReviewData;
use App\Administering\Value\Rolling\AdministrationFieldAccessMutationReviewInput;
use App\Administering\Value\Rolling\AdministrationFieldAccessPolicyDescriptor;
use App\Administering\Value\Rolling\AdministrationFieldAccessTarget;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Review-only surface for Managing field access policy changes.
 */
final class AdministrationManagingFieldAccessMutationReviewController extends AbstractController
{
    public function __construct(
        private readonly AdministrationFieldAccessMutationReviewServiceInterface $reviewService,
        private readonly AdministrationCurrentUserContextProviderInterface $currentUserContextProvider,
    ) {
    }

    #[Route('/admin/managing/field-access-mutations', name: 'administration_managing_field_access_mutations', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.acl_mutation.review.view', 'administering:managing-field-access');

        $form = $this->createForm(AdministrationFieldAccessMutationReviewFormType::class, new AdministrationFieldAccessMutationReviewData(), [
            'action' => $this->generateUrl('administration_managing_field_access_mutation_review'),
        ]);

        return $this->render('@Administering/administering/form_page.html.twig', [
            'page_title' => 'Managing Field Access Mutation Review',
            'lead' => 'Review-only surface. No Rolling ACL changes are applied here.',
            'form' => $form->createView(),
            'result_title' => null,
            'result_json' => null,
            'error_message' => null,
            'action_links' => [],
            'back_url' => null,
        ]);
    }

    #[Route('/admin/managing/field-access-mutations/review', name: 'administration_managing_field_access_mutation_review', methods: ['POST'])]
    public function review(Request $request): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.acl_mutation.review', 'administering:managing-field-access');

        $form = $this->createForm(AdministrationFieldAccessMutationReviewFormType::class, new AdministrationFieldAccessMutationReviewData(), [
            'action' => $this->generateUrl('administration_managing_field_access_mutation_review'),
        ]);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('@Administering/administering/form_page.html.twig', [
                'page_title' => 'Managing Field Access Mutation Review',
                'lead' => 'Review-only surface. No Rolling ACL changes are applied here.',
                'form' => $form->createView(),
                'result_title' => null,
                'result_json' => null,
                'error_message' => null,
                'action_links' => [],
                'back_url' => $this->generateUrl('administration_managing_field_access_mutations'),
            ]);
        }

        $data = $form->getData();
        try {
            $result = $this->reviewService->review(new AdministrationFieldAccessMutationReviewInput(
                $this->descriptorFromData($data),
                $this->currentUserSubject(),
            ));
            $errorMessage = null;
        } catch (\InvalidArgumentException $exception) {
            $result = null;
            $errorMessage = $exception->getMessage();
        }

        if (null === $result) {
            return $this->render('@Administering/administering/form_page.html.twig', [
                'page_title' => 'Managing Field Access Mutation Review',
                'lead' => 'Review-only surface. No Rolling ACL changes are applied here.',
                'form' => $this->createForm(AdministrationFieldAccessMutationReviewFormType::class, $data, [
                    'action' => $this->generateUrl('administration_managing_field_access_mutation_review'),
                ])->createView(),
                'result_title' => null,
                'result_json' => null,
                'error_message' => $errorMessage,
                'action_links' => [],
                'back_url' => $this->generateUrl('administration_managing_field_access_mutations'),
            ]);
        }

        return $this->render('@Administering/administering/form_page.html.twig', [
            'page_title' => 'Managing Field Access Mutation Review',
            'lead' => 'Review-only surface. No Rolling ACL changes are applied here.',
            'form' => $this->createForm(AdministrationFieldAccessMutationReviewFormType::class, $data, [
                'action' => $this->generateUrl('administration_managing_field_access_mutation_review'),
            ])->createView(),
            'result_title' => 'Review result',
            'result_json' => json_encode($result->toSafeArray(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
            'error_message' => null,
            'action_links' => [
                [
                    'label' => 'Open apply surface',
                    'url' => $this->generateUrl('administration_managing_field_access_mutation_apply', ['requestKey' => $result->record->requestKey()]),
                ],
            ],
            'back_url' => $this->generateUrl('administration_managing_field_access_mutations'),
        ]);
    }

    private function descriptorFromData(AdministrationFieldAccessMutationReviewData $data): AdministrationFieldAccessPolicyDescriptor
    {
        return new AdministrationFieldAccessPolicyDescriptor(
            new AdministrationFieldAccessTarget(
                'managing',
                trim($data->resourceClass),
                trim($data->fieldName),
                trim($data->pageName),
                trim($data->operation),
            ),
            trim($data->permissionKey),
            trim($data->subjectType),
            trim($data->subjectIdentifier),
            trim($data->effect),
            trim((string) $data->reason) ?: null,
        );
    }

    private function currentUserSubject(): string
    {
        $currentUser = $this->currentUserContextProvider->current();

        return $currentUser?->subjectIdentifier() ?? 'administering:anonymous';
    }
}
