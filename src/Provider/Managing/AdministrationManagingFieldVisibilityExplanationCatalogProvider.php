<?php

declare(strict_types=1);

namespace App\Administering\Provider\Managing;

use App\Administering\ServiceInterface\Managing\AdministrationFieldVisibilityExplanationCatalogProviderInterface;
use App\Administering\Value\Managing\AdministrationManagingFieldVisibilityExplanationScenario;
use App\Administering\Value\Managing\AdministrationManagingFieldVisibilityExplanationStep;

/**
 * Documents the read-only Administering view of Managing field visibility diagnostics.
 */
final readonly class AdministrationManagingFieldVisibilityExplanationCatalogProvider implements AdministrationFieldVisibilityExplanationCatalogProviderInterface
{
    public function explanationSteps(): array
    {
        return [
            new AdministrationManagingFieldVisibilityExplanationStep(
                10,
                'Page availability',
                'Managing',
                AdministrationManagingFieldVisibilityExplanationStep::AXIS_AVAILABILITY,
                'deny/pass',
                'terminal on unavailable',
                'Availability removes fields unavailable for index/detail/new/edit before access or profile checks.',
            ),
            new AdministrationManagingFieldVisibilityExplanationStep(
                20,
                'Backend access deny config',
                'Managing',
                AdministrationManagingFieldVisibilityExplanationStep::AXIS_ACCESS,
                'deny/pass',
                'terminal on denied',
                'A configured deny is an access-axis decision and cannot be overridden by user profiles.',
            ),
            new AdministrationManagingFieldVisibilityExplanationStep(
                25,
                'Backend presentation config',
                'Managing',
                AdministrationManagingFieldVisibilityExplanationStep::AXIS_PRESENTATION,
                'visible/hidden/pass',
                'non-terminal',
                'Configured visible/hidden rules shape presentation inside an already allowed access corridor.',
            ),
            new AdministrationManagingFieldVisibilityExplanationStep(
                30,
                'External field-value access decision',
                'Rolling',
                AdministrationManagingFieldVisibilityExplanationStep::AXIS_ACCESS,
                'allow/deny/abstain',
                'terminal on deny',
                'Rolling deny blocks field values; allow only opens access and never forces presentation visibility.',
            ),
            new AdministrationManagingFieldVisibilityExplanationStep(
                40,
                'Field definition default',
                'Managing',
                AdministrationManagingFieldVisibilityExplanationStep::AXIS_PRESENTATION,
                'visible/hidden',
                'non-terminal',
                'Metadata defaults provide presentation when no stronger backend presentation rule decided.',
            ),
            new AdministrationManagingFieldVisibilityExplanationStep(
                50,
                'User personal profile',
                'Managing',
                AdministrationManagingFieldVisibilityExplanationStep::AXIS_PRESENTATION,
                'visible/hidden/pass',
                'non-terminal unless rejected',
                'User preference may only affect already allowed and hideable presentation fields.',
            ),
            new AdministrationManagingFieldVisibilityExplanationStep(
                60,
                'Final EasyAdmin emission',
                'Managing',
                AdministrationManagingFieldVisibilityExplanationStep::AXIS_PRESENTATION,
                'render/not-render',
                'final',
                'EasyAdmin receives only fields that remain access-allowed and presentation-visible.',
            ),
        ];
    }

    public function diagnosticScenarios(): array
    {
        return [
            new AdministrationManagingFieldVisibilityExplanationScenario(
                'rolling-deny',
                'Rolling denies field-value access',
                'Field is denied and not emitted.',
                'Rolling returned an access-axis deny decision.',
                'Do not emit the field and surface the Rolling denial as access-axis evidence.',
                ['availability', 'access'],
            ),
            new AdministrationManagingFieldVisibilityExplanationScenario(
                'user-hidden',
                'User hides an allowed field',
                'Field is hidden and not emitted.',
                'A user profile hides a field after access has already been allowed.',
                'Keep access allowed but omit the field from the emitted EasyAdmin field list.',
                ['access', 'presentation'],
            ),
            new AdministrationManagingFieldVisibilityExplanationScenario(
                'required-form-field',
                'User tries to hide a required form field',
                'Field remains visible and emitted.',
                'Required form fields cannot be hidden by presentation profile rules.',
                'Reject the hide request for required or non-hideable form fields.',
                ['availability', 'presentation'],
            ),
            new AdministrationManagingFieldVisibilityExplanationScenario(
                'backend-hidden-user-visible',
                'User shows a backend-hidden presentation default',
                'Field is visible and emitted when access is allowed.',
                'A user profile overrides a presentation default inside an allowed corridor.',
                'Allow the profile to override presentation only after access remains allowed.',
                ['access', 'presentation'],
            ),
            new AdministrationManagingFieldVisibilityExplanationScenario(
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
