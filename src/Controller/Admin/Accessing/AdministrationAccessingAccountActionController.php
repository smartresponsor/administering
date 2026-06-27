<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Accessing;

use App\Administering\Form\Accessing\AdministrationAccessingAccountActionFormType;
use App\Administering\ServiceInterface\Accessing\AdministrationAccountActionRequestRecorderInterface;
use App\Administering\ServiceInterface\Accessing\AdministrationCurrentUserContextProviderInterface;
use App\Administering\Value\Accessing\AdministrationAccountActionDescriptor;
use App\Administering\Value\Accessing\AdministrationAccountActionRequest;
use App\Administering\Value\Accessing\AdministrationAccountActionResult;
use App\Administering\Value\Form\Accessing\AdministrationAccessingAccountActionData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Native Symfony surface for controlled Accessing-owned account actions.
 *
 * The surface creates safe request DTOs only. Accessing owns execution,
 * validation, audit, and all account/session/security internals.
 */
final class AdministrationAccessingAccountActionController extends AbstractController
{
    public function __construct(
        private readonly AdministrationCurrentUserContextProviderInterface $currentUserContextProvider,
        private readonly AdministrationAccountActionRequestRecorderInterface $requestRecorder,
        private readonly FormFactoryInterface $formFactory,
    ) {
    }

    #[Route('/ea/accessing/account-actions', name: 'administration_accessing_account_actions', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('administration.accessing.account_action.view', 'administering:accessing');

        return $this->render('@Administering/administering/accessing_account_actions.html.twig', [
            'action_rows' => $this->actionRows(),
        ]);
    }

    #[Route('/ea/accessing/account-actions/execute', name: 'administration_accessing_account_action_execute', methods: ['POST'])]
    public function execute(Request $request): Response
    {
        $descriptor = $this->descriptorFromRequest($request);
        if (null === $descriptor) {
            throw $this->createNotFoundException('Unknown account action.');
        }

        $this->denyAccessUnlessGranted('administration.accessing.account_action.execute', 'administering:accessing');

        $form = $this->formFactory->createNamed(
            $this->formNameForAction($descriptor->key()),
            AdministrationAccessingAccountActionFormType::class,
            $this->newActionData($descriptor->key()),
            [
                'action' => $this->generateUrl('administration_accessing_account_action_execute'),
                'requires_reason' => $descriptor->requiresReason(),
                'csrf_token_id' => $this->csrfTokenId($descriptor->key()),
            ],
        );
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('@Administering/administering/accessing_account_actions.html.twig', [
                'action_rows' => $this->actionRows($descriptor->key(), $form),
            ]);
        }

        $data = $form->getData();
        $currentUser = $this->currentUserContextProvider->current();
        $actionRequest = new AdministrationAccountActionRequest(
            $descriptor->key(),
            trim($data->accountReference),
            $currentUser?->subjectIdentifier() ?? 'administering:anonymous',
            trim($data->reason) ?: 'administrative request',
            [
                'source' => 'administering_ui',
                'surface' => 'accessing_account_actions',
            ],
        );

        $result = AdministrationAccountActionResult::recorded();
        $record = $this->requestRecorder->record($actionRequest, $result);

        $this->addFlash(
            $result->succeeded() ? 'success' : 'warning',
            sprintf('%s Request record: %s.', $result->safeMessage(), $record->requestKey()),
        );

        return $this->redirectToRoute('administration_accessing_account_actions');
    }

    /** @return list<array{descriptor: AdministrationAccountActionDescriptor, form: \Symfony\Component\Form\FormView}> */
    private function actionRows(?string $selectedAction = null, ?\Symfony\Component\Form\FormInterface $submittedForm = null): array
    {
        $rows = [];

        foreach ($this->descriptors() as $descriptor) {
            if (null !== $selectedAction && $descriptor->key() === $selectedAction && null !== $submittedForm) {
                $formView = $submittedForm->createView();
            } else {
                $form = $this->formFactory->createNamed(
                    $this->formNameForAction($descriptor->key()),
                    AdministrationAccessingAccountActionFormType::class,
                    $this->newActionData($descriptor->key()),
                    [
                        'action' => $this->generateUrl('administration_accessing_account_action_execute'),
                        'requires_reason' => $descriptor->requiresReason(),
                        'csrf_token_id' => $this->csrfTokenId($descriptor->key()),
                    ],
                );
                $formView = $form->createView();
            }

            $rows[] = [
                'descriptor' => $descriptor,
                'form' => $formView,
            ];
        }

        return $rows;
    }

    private function descriptorFromRequest(Request $request): ?AdministrationAccountActionDescriptor
    {
        foreach ($this->descriptors() as $descriptor) {
            if ($request->request->has($this->formNameForAction($descriptor->key()))) {
                return $descriptor;
            }
        }

        return null;
    }

    /** @return list<AdministrationAccountActionDescriptor> */
    private function descriptors(): array
    {
        return [
            new AdministrationAccountActionDescriptor('account.review', 'Review account', 'low', false),
            new AdministrationAccountActionDescriptor('account.disable', 'Request account disable', 'high', true),
            new AdministrationAccountActionDescriptor('session.revoke', 'Request session revoke', 'medium', true),
            new AdministrationAccountActionDescriptor('credential.reset', 'Request credential reset', 'high', true),
        ];
    }

    private function newActionData(string $action): AdministrationAccessingAccountActionData
    {
        $data = new AdministrationAccessingAccountActionData();
        $data->action = $action;

        return $data;
    }

    private function formNameForAction(string $action): string
    {
        return 'administering_accessing_account_action_'.strtr($action, [
            '.' => '_',
            ':' => '_',
            '-' => '_',
        ]);
    }

    private function csrfTokenId(string $action): string
    {
        return 'administering.accessing.account_action.'.$action;
    }
}
