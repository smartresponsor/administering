<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use App\Administering\Form\Rolling\AdministrationRollingAclMutationReviewFormType;
use App\Administering\ServiceInterface\Accessing\AdministrationCurrentUserContextProviderInterface;
use App\Administering\ServiceInterface\Rolling\AdministrationAclMutationReviewRecorderInterface;
use App\Administering\Value\Form\Rolling\AdministrationRollingAclMutationReviewData;
use App\Rolling\ServiceInterface\Administration\RollingAclMutationReviewBuilderInterface;
use App\Rolling\Value\Administration\RollingAclMutationRequest;
use App\Rolling\Value\Administration\RollingFieldAccessDecisionRequest;
use App\Rolling\Value\Administration\RollingFieldAccessScopeSet;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Native Symfony surface for safe Rolling ACL mutation dry-run reviews.
 *
 * This controller does not execute ACL mutations. It only builds review metadata
 * so an operator can see whether a request is valid before a future apply step.
 */
final class AdministrationRollingAclMutationController extends AbstractController
{
    public function __construct(
        private readonly RollingAclMutationReviewBuilderInterface $reviewBuilder,
        private readonly AdministrationCurrentUserContextProviderInterface $currentUserContextProvider,
        private readonly AdministrationAclMutationReviewRecorderInterface $reviewRecorder,
    ) {
    }

    #[Route('/admin/rolling/acl-mutations', name: 'administration_rolling_acl_mutations', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.acl_mutation.review.view', 'administering:rolling');

        $form = $this->createForm(AdministrationRollingAclMutationReviewFormType::class, new AdministrationRollingAclMutationReviewData(), [
            'action' => $this->generateUrl('administration_rolling_acl_mutation_review'),
        ]);

        return $this->render('@Administering/administering/form_page.html.twig', [
            'page_title' => 'Rolling ACL Mutation Review',
            'lead' => 'Dry-run review only. Rolling owns authorization and ACL persistence.',
            'form' => $form->createView(),
            'result_title' => null,
            'result_json' => null,
            'error_message' => null,
            'action_links' => [],
            'back_url' => null,
        ]);
    }

    #[Route('/admin/rolling/acl-mutations/review', name: 'administration_rolling_acl_mutation_review', methods: ['POST'])]
    public function review(Request $request): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.acl_mutation.review', 'administering:rolling');

        $form = $this->createForm(AdministrationRollingAclMutationReviewFormType::class, new AdministrationRollingAclMutationReviewData(), [
            'action' => $this->generateUrl('administration_rolling_acl_mutation_review'),
        ]);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('@Administering/administering/form_page.html.twig', [
                'page_title' => 'Rolling ACL Mutation Review',
                'lead' => 'Dry-run review only. Rolling owns authorization and ACL persistence.',
                'form' => $form->createView(),
                'result_title' => null,
                'result_json' => null,
                'error_message' => null,
                'action_links' => [],
                'back_url' => $this->generateUrl('administration_rolling_acl_mutations'),
            ]);
        }

        $data = $form->getData();
        $currentUser = $this->currentUserContextProvider->current();
        $mutationRequest = new RollingAclMutationRequest(
            trim($data->mutationType),
            trim($data->subjectIdentifier),
            trim($data->permissionOrRoleKey),
            $this->scopeKey($data),
            $currentUser?->subjectIdentifier() ?? 'administering:anonymous',
            [
                'source' => 'administering_ui',
                'surface' => 'rolling_acl_mutation_review',
            ],
        );

        try {
            $review = $this->reviewBuilder->review($mutationRequest);
            $record = $this->reviewRecorder->record($mutationRequest, $review);
            $errorMessage = null;
        } catch (\InvalidArgumentException $exception) {
            $review = null;
            $record = null;
            $errorMessage = $exception->getMessage();
        }

        if (null === $review || null === $record) {
            return $this->render('@Administering/administering/form_page.html.twig', [
                'page_title' => 'Rolling ACL Mutation Review',
                'lead' => 'Dry-run review only. Rolling owns authorization and ACL persistence.',
                'form' => $this->createForm(AdministrationRollingAclMutationReviewFormType::class, $data, [
                    'action' => $this->generateUrl('administration_rolling_acl_mutation_review'),
                ])->createView(),
                'result_title' => null,
                'result_json' => null,
                'error_message' => $errorMessage,
                'action_links' => [],
                'back_url' => $this->generateUrl('administration_rolling_acl_mutations'),
            ]);
        }

        return $this->render('@Administering/administering/form_page.html.twig', [
            'page_title' => 'Rolling ACL Mutation Review',
            'lead' => 'Dry-run review only. Rolling owns authorization and ACL persistence.',
            'form' => $this->createForm(AdministrationRollingAclMutationReviewFormType::class, $data, [
                'action' => $this->generateUrl('administration_rolling_acl_mutation_review'),
            ])->createView(),
            'result_title' => sprintf('Persisted review record: %s', $record->requestKey()),
            'result_json' => json_encode($review->toSafeArray(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
            'error_message' => null,
            'action_links' => [],
            'back_url' => $this->generateUrl('administration_rolling_acl_mutations'),
        ]);
    }

    private function scopeKey(AdministrationRollingAclMutationReviewData $data): string
    {
        return RollingFieldAccessScopeSet::fromRequest(new RollingFieldAccessDecisionRequest(
            permissionKey: trim($data->permissionOrRoleKey),
            componentKey: trim($data->componentKey),
            resourceClass: trim($data->resourceClass),
            fieldName: trim($data->fieldName),
            pageName: trim($data->pageName),
            operation: 'view',
        ))->mostSpecificScope();
    }
}
