<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use App\Administering\Form\Rolling\AdministrationRollingAclMutationReviewFormType;
use App\Administering\ServiceInterface\Accessing\AdministrationCurrentUserContextProviderInterface;
use App\Administering\ServiceInterface\Rolling\AdministrationAclMutationReviewRecorderInterface;
use App\Administering\Value\Form\Rolling\AdministrationRollingAclMutationReviewData;
use App\Administering\Value\Rolling\AdministrationRollingAclMutationRequest;
use App\Administering\Value\Rolling\AdministrationRollingAclMutationReview;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Native Symfony surface for safe ACL mutation dry-run reviews.
 *
 * This controller does not execute ACL mutations and does not depend on Rolling.
 * It only builds and persists safe Administering-owned review metadata.
 */
final class AdministrationRollingAclMutationController extends AbstractController
{
    private const ALLOWED_MUTATION_TYPES = [
        'permission.grant',
        'permission.revoke',
        'acl.allow',
        'acl.deny',
    ];

    public function __construct(
        private readonly AdministrationCurrentUserContextProviderInterface $currentUserContextProvider,
        private readonly AdministrationAclMutationReviewRecorderInterface $reviewRecorder,
    ) {
    }

    #[Route('/ea/role/mutation', name: 'administration_rolling_acl_mutations', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.acl_mutation.review.view', 'administering:rolling');

        $form = $this->createForm(AdministrationRollingAclMutationReviewFormType::class, new AdministrationRollingAclMutationReviewData(), [
            'action' => $this->generateUrl('administration_rolling_acl_mutation_review'),
        ]);

        return $this->render('@Administering/administering/form_page.html.twig', [
            'page_title' => 'ACL Mutation Review',
            'lead' => 'Dry-run review only. Administering records safe metadata; Rolling owns real authorization and ACL persistence.',
            'form' => $form->createView(),
            'result_title' => null,
            'result_json' => null,
            'error_message' => null,
            'action_links' => [],
            'back_url' => null,
        ]);
    }

    #[Route('/ea/role/mutation/review', name: 'administration_rolling_acl_mutation_review', methods: ['POST'])]
    public function review(Request $request): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.acl_mutation.review', 'administering:rolling');

        $form = $this->createForm(AdministrationRollingAclMutationReviewFormType::class, new AdministrationRollingAclMutationReviewData(), [
            'action' => $this->generateUrl('administration_rolling_acl_mutation_review'),
        ]);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->renderFormPage($form->createView(), null, null, null);
        }

        $data = $form->getData();
        if (!$data instanceof AdministrationRollingAclMutationReviewData) {
            throw new \LogicException('ACL mutation review form must return AdministrationRollingAclMutationReviewData.');
        }

        try {
            $mutationRequest = $this->mutationRequest($data);
            $review = $this->buildReview($data, $mutationRequest);
            $record = $this->reviewRecorder->record($mutationRequest, $review);
            $errorMessage = null;
        } catch (\InvalidArgumentException $exception) {
            $review = null;
            $record = null;
            $errorMessage = $exception->getMessage();
        }

        if (null === $review) {
            return $this->renderFormPage(
                $this->createForm(AdministrationRollingAclMutationReviewFormType::class, $data, [
                    'action' => $this->generateUrl('administration_rolling_acl_mutation_review'),
                ])->createView(),
                null,
                null,
                $errorMessage,
            );
        }

        return $this->renderFormPage(
            $this->createForm(AdministrationRollingAclMutationReviewFormType::class, $data, [
                'action' => $this->generateUrl('administration_rolling_acl_mutation_review'),
            ])->createView(),
            sprintf('Persisted review record: %s', $record->requestKey()),
            json_encode($review->toSafeArray(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
            null,
        );
    }

    private function mutationRequest(AdministrationRollingAclMutationReviewData $data): AdministrationRollingAclMutationRequest
    {
        $currentUser = $this->currentUserContextProvider->current();

        return new AdministrationRollingAclMutationRequest(
            trim($data->mutationType),
            trim($data->subjectIdentifier),
            trim($data->permissionOrRoleKey),
            $this->scopeKey($data),
            $currentUser?->subjectIdentifier() ?? 'administering:anonymous',
            [
                'source' => 'administering_ui',
                'surface' => 'acl_mutation_review',
                'component_key' => trim($data->componentKey),
                'resource_class' => trim($data->resourceClass),
                'page_name' => trim($data->pageName),
                'field_name' => trim($data->fieldName),
            ],
        );
    }

    private function buildReview(
        AdministrationRollingAclMutationReviewData $data,
        AdministrationRollingAclMutationRequest $request,
    ): AdministrationRollingAclMutationReview {
        $violations = [];
        $warnings = [];

        if (!in_array($request->mutationType(), self::ALLOWED_MUTATION_TYPES, true)) {
            $violations[] = sprintf('Unsupported mutation type: %s', $request->mutationType());
        }

        foreach ([
            'subject identifier' => $request->subjectIdentifier(),
            'permission or role key' => $request->permissionOrRoleKey(),
            'component key' => $data->componentKey,
            'resource class' => $data->resourceClass,
            'page nameEntity' => $data->pageName,
            'field nameEntity' => $data->fieldName,
        ] as $label => $value) {
            if ('' === trim((string) $value)) {
                $violations[] = sprintf('Missing %s.', $label);
            }
        }

        if (!str_contains(trim($data->resourceClass), '\\')) {
            $warnings[] = 'Resource class does not look like a fully qualified PHP class AdministrationRollingAclMutationController.';
        }

        return new AdministrationRollingAclMutationReview(
            $request->mutationType(),
            $request->subjectIdentifier(),
            $request->permissionOrRoleKey(),
            $request->scopeKey(),
            [] === $violations,
            [
                'Collected operator ACL mutation request.',
                'Computed Administering-owned scope key.',
                'Persisted safe review metadata without calling optional Rolling services.',
            ],
            $warnings,
            $violations,
            $request->safeContext(),
        );
    }

    private function scopeKey(AdministrationRollingAclMutationReviewData $data): string
    {
        return implode(':', array_filter([
            trim($data->componentKey),
            str_replace('\\', '.', trim($data->resourceClass)),
            trim($data->pageName),
            trim($data->fieldName),
            'view',
        ], static fn (string $part): bool => '' !== $part));
    }

    private function renderFormPage(
        FormView $formView,
        ?string $resultTitle,
        ?string $resultJson,
        ?string $errorMessage,
    ): Response {
        return $this->render('@Administering/administering/form_page.html.twig', [
            'page_title' => 'ACL Mutation Review',
            'lead' => 'Dry-run review only. Administering records safe metadata; Rolling owns real authorization and ACL persistence.',
            'form' => $formView,
            'result_title' => $resultTitle,
            'result_json' => $resultJson,
            'error_message' => $errorMessage,
            'action_links' => [],
            'back_url' => $this->generateUrl('administration_rolling_acl_mutations'),
        ]);
    }
}
