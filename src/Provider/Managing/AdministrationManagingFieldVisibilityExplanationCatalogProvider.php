<?php

declare(strict_types=1);

namespace App\Administering\Provider\Managing;

use App\Administering\ServiceInterface\Managing\AdministrationFieldVisibilityExplanationCatalogProviderInterface;
use App\Administering\Value\Managing\ManagingFieldVisibilityExplanationScenario;
use App\Administering\Value\Managing\ManagingFieldVisibilityExplanationStep;

/**
 * Documents the read-only Administering view of Managing field visibility diagnostics.
 */
final readonly class AdministrationManagingFieldVisibilityExplanationCatalogProvider implements AdministrationFieldVisibilityExplanationCatalogProviderInterface
{
    public function explanationSteps(): array
    {
        return [
            new ManagingFieldVisibilityExplanationStep(
                10,
                'Page availability',
                'Managing',
                ManagingFieldVisibilityExplanationStep::AXIS_AVAILABILITY,
                'deny/pass',
                'terminal on unavailable',
                'Availability removes fields unavailable for index/detail/new/edit before access or profile checks.',
            ),
            new ManagingFieldVisibilityExplanationStep(
                20,
                'Backend access deny config',
                'Managing',
                ManagingFieldVisibilityExplanationStep::AXIS_ACCESS,
                'deny/pass',
                'terminal on denied',
                'A configured deny is an access-axis decision and cannot be overridden by user profiles.',
            ),
            new ManagingFieldVisibilityExplanationStep(
                25,
                'Backend presentation config',
                'Managing',
                ManagingFieldVisibilityExplanationStep::AXIS_PRESENTATION,
                'visible/hidden/pass',
                'non-terminal',
                'Configured visible/hidden rules shape presentation inside an already allowed access corridor.',
            ),
            new ManagingFieldVisibilityExplanationStep(
                30,
                'External field-value access decision',
                'Rolling',
                ManagingFieldVisibilityExplanationStep::AXIS_ACCESS,
                'allow/deny/abstain',
                'terminal on deny',
                'Rolling deny blocks field values; allow only opens access and never forces presentation visibility.',
            ),
            new ManagingFieldVisibilityExplanationStep(
                40,
                'Field definition default',
                'Managing',
                ManagingFieldVisibilityExplanationStep::AXIS_PRESENTATION,
                'visible/hidden',
                'non-terminal',
                'Metadata defaults provide presentation when no stronger backend presentation rule decided.',
            ),
            new ManagingFieldVisibilityExplanationStep(
                50,
                'User personal profile',
                'Managing',
                ManagingFieldVisibilityExplanationStep::AXIS_PRESENTATION,
                'visible/hidden/pass',
                'non-terminal unless rejected',
                'User preference may only affect already allowed and hideable presentation fields.',
            ),
            new ManagingFieldVisibilityExplanationStep(
                60,
                'Final EasyAdmin emission',
                'Managing',
                ManagingFieldVisibilityExplanationStep::AXIS_PRESENTATION,
                'render/not-render',
                'final',
                'EasyAdmin receives only fields that remain access-allowed and presentation-visible.',
            ),
        ];
    }

    public function diagnosticScenarios(): array
    {
        return [
            new ManagingFieldVisibilityExplanationScenario(
                'rolling-deny',
                'Rolling denies field-value access',
                'Field is denied and not emitted.',
                'Rolling returned an access-axis deny decision.',
                'Do not emit the field and surface the Rolling denial as access-axis evidence.',
                ['availability', 'access'],
            ),
            new ManagingFieldVisibilityExplanationScenario(
                'user-hidden',
                'User hides an allowed field',
                'Field is hidden and not emitted.',
                'A user profile hides a field after access has already been allowed.',
                'Keep access allowed but omit the field from the emitted EasyAdmin field list.',
                ['access', 'presentation'],
            ),
            new ManagingFieldVisibilityExplanationScenario(
                'required-form-field',
                'User tries to hide a required form field',
                'Field remains visible and emitted.',
                'Required form fields cannot be hidden by presentation profile rules.',
                'Reject the hide request for required or non-hideable form fields.',
                ['availability', 'presentation'],
            ),
            new ManagingFieldVisibilityExplanationScenario(
                'backend-hidden-user-visible',
                'User shows a backend-hidden presentation default',
                'Field is visible and emitted when access is allowed.',
                'A user profile overrides a presentation default inside an allowed corridor.',
                'Allow the profile to override presentation only after access remains allowed.',
                ['access', 'presentation'],
            ),
            new ManagingFieldVisibilityExplanationScenario(
                'page-unavailable',
                'Field is unavailable on the requested page',
                'Field is denied and not emitted.',
                'The field is unavailable for the current EasyAdmin page.',
                'Stop before access and presentation checks because the field is unavailable on the page.',
                ['availability'],
            ),
        ];
    }
}
