<?php

declare(strict_types=1);

namespace App\Administering\Service\Rolling;

use App\Administering\ServiceInterface\Rolling\AdministrationFieldViewProfileCatalogProviderInterface;
use App\Administering\Value\Rolling\AdministrationFieldViewProfileCatalogItem;
use App\Administering\Value\Rolling\AdministrationFieldViewProfilePriorityRow;
use App\Administering\Value\Rolling\AdministrationFieldViewProfileRuleShape;
use App\Administering\Value\Rolling\AdministrationManagingFieldPermissionVocabulary;

/**
 * Builds read-only Administering metadata for Managing field view profile administration.
 */
final readonly class AdministrationManagingFieldViewProfileCatalogProvider implements AdministrationFieldViewProfileCatalogProviderInterface
{
    public function catalogItems(): array
    {
        return [
            new AdministrationFieldViewProfileCatalogItem(
                'system-default',
                'System default field presentation',
                'Managing',
                'Managing config / Administering future storage',
                'Cannot override hard deny or Rolling deny',
                ['visible', 'hidden'],
                'Baseline presentation defaults applied after access has been allowed.',
            ),
            new AdministrationFieldViewProfileCatalogItem(
                'role-default',
                'Role default view profile',
                'Administering',
                'System SQLite control-plane storage',
                AdministrationManagingFieldPermissionVocabulary::PROFILE_ROLE_UPDATE,
                ['visible', 'hidden', 'assign'],
                'Admin-assigned role default can shape presentation but cannot grant field access.',
            ),
            new AdministrationFieldViewProfileCatalogItem(
                'group-default',
                'Group default view profile',
                'Administering',
                'System SQLite control-plane storage',
                AdministrationManagingFieldPermissionVocabulary::PROFILE_GROUP_UPDATE,
                ['visible', 'hidden', 'assign'],
                'Admin-assigned group default is evaluated inside the already allowed access corridor.',
            ),
            new AdministrationFieldViewProfileCatalogItem(
                'user-default',
                'User assigned default view profile',
                'Administering',
                'System SQLite control-plane storage',
                AdministrationManagingFieldPermissionVocabulary::PROFILE_USER_UPDATE,
                ['visible', 'hidden', 'assign', 'reset'],
                'Administrator can inspect or reset a user profile without changing field access policy.',
            ),
            new AdministrationFieldViewProfileCatalogItem(
                'user',
                'User personal view profile',
                'Managing',
                'Managing execution seam / future system storage',
                AdministrationManagingFieldPermissionVocabulary::PROFILE_SELF_UPDATE,
                ['visible', 'hidden', 'reset-self'],
                'User can only hide or show fields already allowed by system, Rolling, and admin policy.',
            ),
        ];
    }

    public function priorityRows(): array
    {
        return [
            new AdministrationFieldViewProfilePriorityRow(10, 'System/component hard deny', 'Managing', 'deny', 'nothing', 'Unavailable, denied, or non-page fields are removed before user profile logic.'),
            new AdministrationFieldViewProfilePriorityRow(20, 'Effective security decision', 'Rolling', 'allow/deny/abstain', 'presentation only', 'Rolling deny remains stronger than every profile or UI preference.'),
            new AdministrationFieldViewProfilePriorityRow(30, 'Admin field policy', 'Administering/Rolling', 'allow/deny/profile assignment', 'presentation only', 'Admin-assigned access policy decides the corridor in which profiles may operate.'),
            new AdministrationFieldViewProfilePriorityRow(40, 'Role/group/user default profile', 'Administering', 'visible/hidden', 'system defaults only', 'Default profiles may shape presentation but cannot create access.'),
            new AdministrationFieldViewProfilePriorityRow(50, 'User personal view profile', 'Managing', 'visible/hidden', 'allowed presentation', 'Personal preference can override visibility only for hideable and allowed fields.'),
            new AdministrationFieldViewProfilePriorityRow(60, 'EasyAdmin field emission', 'Managing', 'render/not-render', 'none', 'EasyAdmin receives only the final allowed and visible field set.'),
        ];
    }

    public function ruleShapes(): array
    {
        return [
            new AdministrationFieldViewProfileRuleShape(
                'subjects',
                'subjects.{subjectIdentifier}',
                ['defaults', 'resources'],
                'selects the subject-specific profile',
                'Subject identifiers may be exact, such as user:42, or wildcard * for shared defaults.',
            ),
            new AdministrationFieldViewProfileRuleShape(
                'defaults',
                'subjects.{subject}.defaults.{page}',
                ['visible', 'hidden'],
                'applies page-wide defaults',
                'Page may be index, detail, new, edit, all, or *.',
            ),
            new AdministrationFieldViewProfileRuleShape(
                'resources',
                'subjects.{subject}.resources.{resourceClass}.{page}',
                ['visible', 'hidden'],
                'applies resource/page overrides',
                'Resource-specific page rules win over subject defaults.',
            ),
            new AdministrationFieldViewProfileRuleShape(
                'visible',
                'visible: [fieldName]',
                ['field names'],
                'requests presentation visible',
                'Visible never grants access; denied fields still remain unavailable.',
            ),
            new AdministrationFieldViewProfileRuleShape(
                'hidden',
                'hidden: [fieldName]',
                ['field names'],
                'requests presentation hidden',
                'Required or non-hideable form fields must remain visible on new/edit.',
            ),
        ];
    }
}
