<?php

declare(strict_types=1);

namespace App\Administering\Service\Managing;

use App\Administering\ServiceInterface\Accessing\AdministrationCurrentUserContextProviderInterface;
use App\Administering\ServiceInterface\Admin\AdministrationServiceToolHandlerInterface;
use App\Administering\ServiceInterface\Managing\AdministrationFieldAccessMutationApplyServiceInterface;
use App\Administering\Value\Admin\AdministrationServiceToolInvocation;
use App\Administering\Value\Operation\AdministrationOperationExecutionResult;
use App\Managing\ServiceInterface\Administration\ManagingFieldAccessMutationApplyServiceInterface;
use App\Managing\Value\Administration\ManagingAclMutationApplyResult;

/**
 * Thin Administering adapter for the owner-side Managing field access apply service.
 */
final readonly class AdministrationManagingFieldAccessMutationApplyService implements AdministrationFieldAccessMutationApplyServiceInterface, AdministrationServiceToolHandlerInterface
{
    public function __construct(
        private AdministrationCurrentUserContextProviderInterface $currentUserContextProvider,
        private ManagingFieldAccessMutationApplyServiceInterface $applyService,
    ) {
    }

    public function handleAdministrationServiceTool(AdministrationServiceToolInvocation $invocation): AdministrationOperationExecutionResult
    {
        $requestKey = $invocation->stringFormValue('requestKey');
        if ('' === $requestKey) {
            return AdministrationOperationExecutionResult::failed('Review request key is required to apply a Managing field-access mutation.', [
                'tool_key' => $invocation->toolKey,
                'reason' => 'missing_request_key',
            ]);
        }

        $result = $this->applyReviewedFieldAccessMutation($requestKey, $this->requestedBySubject());

        return $result->succeeded()
            ? AdministrationOperationExecutionResult::succeeded($result->safeMessage(), $this->executionSafeContext($invocation, $result))
            : AdministrationOperationExecutionResult::failed($result->safeMessage(), $this->executionSafeContext($invocation, $result));
    }

    public function applyReviewedFieldAccessMutation(string $requestKey, string $requestedBySubject): ManagingAclMutationApplyResult
    {
        $result = $this->applyService->applyReviewedFieldAccessMutation($requestKey, $requestedBySubject);

        return ManagingAclMutationApplyResult::fromRollingResult(
            $result->requestKey(),
            $result->succeeded(),
            $result->status(),
            $result->safeMessage(),
            $result->safeContext(),
        );
    }

    private function requestedBySubject(): string
    {
        return $this->currentUserContextProvider->current()?->subjectIdentifier() ?? 'administering:service-tool';
    }

    /** @return array<string, mixed> */
    private function executionSafeContext(AdministrationServiceToolInvocation $invocation, ManagingAclMutationApplyResult $result): array
    {
        return [
            'tool_key' => $invocation->toolKey,
            'section_key' => $invocation->sectionKey,
            'tool_slug' => $invocation->toolSlug,
            'request_key' => $result->requestKey(),
            'result_status' => $result->status(),
            'result_succeeded' => $result->succeeded(),
            'result_context' => $result->safeContext(),
        ];
    }
}
