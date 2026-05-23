<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Surface;

use App\Administering\Form\Rolling\AdministrationRollingAclMutationApplyFormType;
use App\Administering\ServiceInterface\Accessing\AdministrationCurrentUserContextProviderInterface;
use App\Administering\ServiceInterface\Rolling\AdministrationAclMutationApplyServiceInterface;
use App\Administering\Value\Form\Rolling\AdministrationRollingAclMutationApplyData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Native surface for promoting a previously reviewed Rolling ACL mutation to a
 * controlled Rolling-owned apply attempt.
 */
final class AdministrationRollingAclMutationApplyController extends AbstractController
{
    public function __construct(
        private readonly AdministrationAclMutationApplyServiceInterface $applyService,
        private readonly AdministrationCurrentUserContextProviderInterface $currentUserContextProvider,
    ) {
    }

    #[Route('/admin/rolling/acl-mutations/apply', name: 'administration_rolling_acl_mutation_apply', methods: ['GET', 'POST'])]
    public function apply(Request $request): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.acl_mutation.apply', 'administering:rolling');

        $data = new AdministrationRollingAclMutationApplyData();
        $data->requestKey = trim((string) $request->query->get('requestKey', ''));

        $form = $this->createForm(AdministrationRollingAclMutationApplyFormType::class, $data, [
            'action' => $this->generateUrl('administration_rolling_acl_mutation_apply'),
        ]);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('@Administering/administering/form_page.html.twig', [
                'page_title' => 'Apply Reviewed Rolling ACL Mutation',
                'lead' => 'This surface applies only an existing persisted dry-run review. Rolling remains the ACL execution owner.',
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
            $result = $this->applyService->applyReviewedMutation(
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
                'page_title' => 'Rolling ACL Apply Result',
                'lead' => 'This surface applies only an existing persisted dry-run review. Rolling remains the ACL execution owner.',
                'form' => $this->createForm(AdministrationRollingAclMutationApplyFormType::class, $data, [
                    'action' => $this->generateUrl('administration_rolling_acl_mutation_apply'),
                ])->createView(),
                'result_title' => null,
                'result_json' => null,
                'error_message' => $errorMessage,
                'action_links' => [],
                'back_url' => $this->generateUrl('administration_rolling_acl_mutation_apply'),
            ]);
        }

        return $this->render('@Administering/administering/form_page.html.twig', [
            'page_title' => 'Rolling ACL Apply Result',
            'lead' => 'This surface applies only an existing persisted dry-run review. Rolling remains the ACL execution owner.',
            'form' => $this->createForm(AdministrationRollingAclMutationApplyFormType::class, $data, [
                'action' => $this->generateUrl('administration_rolling_acl_mutation_apply'),
            ])->createView(),
            'result_title' => 'Apply result',
            'result_json' => json_encode($result->toSafeArray(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
            'error_message' => null,
            'action_links' => [],
            'back_url' => $this->generateUrl('administration_rolling_acl_mutation_apply'),
        ]);
    }
}
