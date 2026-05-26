<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use App\Administering\Form\Managing\AdministrationManagingFieldAccessMutationApplyFormType;
use App\Administering\ServiceInterface\Accessing\AdministrationCurrentUserContextProviderInterface;
use App\Administering\ServiceInterface\Managing\AdministrationFieldAccessMutationApplyServiceInterface;
use App\Administering\Value\Form\Managing\AdministrationManagingFieldAccessMutationApplyData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Applies a persisted Managing field-access review through the Rolling-owned ACL
 * execution flow after field-specific guard checks pass.
 */
final class AdministrationManagingFieldAccessMutationApplyController extends AbstractController
{
    public function __construct(
        private readonly AdministrationFieldAccessMutationApplyServiceInterface $applyService,
        private readonly AdministrationCurrentUserContextProviderInterface $currentUserContextProvider,
    ) {
    }

    #[Route('/admin/managing/field-access-mutations/apply', name: 'administration_managing_field_access_mutation_apply', methods: ['GET', 'POST'])]
    public function apply(Request $request): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.acl_mutation.apply.view', 'administering:managing-field-access');

        $data = new AdministrationManagingFieldAccessMutationApplyData();
        $data->requestKey = trim((string) $request->query->get('requestKey', ''));

        $form = $this->createForm(AdministrationManagingFieldAccessMutationApplyFormType::class, $data, [
            'action' => $this->generateUrl('administration_managing_field_access_mutation_apply'),
        ]);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('@Administering/administering/form_page.html.twig', [
                'page_title' => 'Apply Managing Field Access Review',
                'lead' => 'This surface applies only persisted Managing field-access review records. Broader Rolling ACL reviews are rejected here.',
                'form' => $form->createView(),
                'result_title' => null,
                'result_json' => null,
                'error_message' => null,
                'action_links' => [],
                'back_url' => null,
            ]);
        }

        $currentUser = $this->currentUserContextProvider->current();
        try {
            $result = $this->applyService->applyReviewedFieldAccessMutation(
                trim($form->getData()->requestKey),
                $currentUser?->subjectIdentifier() ?? 'administering:anonymous',
            );
            $errorMessage = null;
        } catch (\InvalidArgumentException $exception) {
            $result = null;
            $errorMessage = $exception->getMessage();
        }

        if (null === $result) {
            return $this->render('@Administering/administering/form_page.html.twig', [
                'page_title' => 'Managing Field Access Apply Result',
                'lead' => 'This surface applies only persisted Managing field-access review records. Broader Rolling ACL reviews are rejected here.',
                'form' => $this->createForm(AdministrationManagingFieldAccessMutationApplyFormType::class, $data, [
                    'action' => $this->generateUrl('administration_managing_field_access_mutation_apply'),
                ])->createView(),
                'result_title' => null,
                'result_json' => null,
                'error_message' => $errorMessage,
                'action_links' => [],
                'back_url' => $this->generateUrl('administration_managing_field_access_mutation_apply'),
            ]);
        }

        return $this->render('@Administering/administering/form_page.html.twig', [
            'page_title' => 'Managing Field Access Apply Result',
            'lead' => 'This surface applies only persisted Managing field-access review records. Broader Rolling ACL reviews are rejected here.',
            'form' => $this->createForm(AdministrationManagingFieldAccessMutationApplyFormType::class, $data, [
                'action' => $this->generateUrl('administration_managing_field_access_mutation_apply'),
            ])->createView(),
            'result_title' => 'Apply result',
            'result_json' => json_encode($result->toSafeArray(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
            'error_message' => null,
            'action_links' => [],
            'back_url' => $this->generateUrl('administration_managing_field_access_mutation_apply'),
        ]);
    }
}
