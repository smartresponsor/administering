<?php

declare(strict_types=1);

namespace App\Administering\Provider\Managing;

use App\Administering\ServiceInterface\Managing\AdministrationFieldViewProfileCatalogProviderInterface;
use App\Administering\Value\Managing\ManagingFieldViewProfileCatalogItem;
use App\Administering\Value\Managing\ManagingFieldViewProfilePriorityRow;
use App\Administering\Value\Managing\ManagingFieldViewProfileRuleShape;

/**
 * Builds read-only Administering metadata for Managing field view profile administration.
 */
final readonly class AdministrationManagingFieldViewProfileCatalogProvider implements AdministrationFieldViewProfileCatalogProviderInterface
{
    public function catalogItems(): array
    {
        return [
            new ManagingFieldViewProfileCatalogItem(
                'system-default',
                'System default field presentation',
                'Managing',
                'Managing config / Administering future storage',
                false,
                ['visible', 'hidden'],
                'Baseline presentation defaults applied after access has been allowed.',
            ),
            new ManagingFieldViewProfileCatalogItem(
                'role-default',
                'Role default view profile',
                'Administering',
                'System SQLite control-plane storage',
                true,
                ['visible', 'hidden', 'assign'],
                'Admin-assigned role default can shape presentation but cannot grant field access.',
            ),
            new ManagingFieldViewProfileCatalogItem(
                'group-default',
                'Group default view profile',
                'Administering',
                'System SQLite control-plane storage',
                true,
                ['visible', 'hidden', 'assign'],
                'Admin-assigned group default is evaluated inside the already allowed access corridor.',
            ),
            new ManagingFieldViewProfileCatalogItem(
                'user-default',
                'User assigned default view profile',
                'Administering',
                'System SQLite control-plane storage',
                true,
                ['visible', 'hidden', 'assign', 'reset'],
                'Administrator can inspect or reset a user profile without changing field access policy.',
            ),
            new ManagingFieldViewProfileCatalogItem(
                'user',
                'User personal view profile',
                'Managing',
                'Managing execution seam / future system storage',
                true,
                ['visible', 'hidden', 'reset-self'],
                'User can only hide or show fields already allowed by system, Rolling, and admin policy.',
            ),
        ];
    }

    public function priorityRows(): array
    {
        return [
            new ManagingFieldViewProfilePriorityRow(10, 'System/component hard deny', 'Managing', 'deny', 'nothing', 'Unavailable, denied, or non-page fields are removed before user profile logic.'),
            new ManagingFieldViewProfilePriorityRow(20, 'Effective security decision', 'Rolling', 'allow/deny/abstain', 'presentation only', 'Rolling deny remains stronger than every profile or UI preference.'),
            new ManagingFieldViewProfilePriorityRow(30, 'Admin field policy', 'Administering/Rolling', 'allow/deny/profile assignment', 'presentation only', 'Admin-assigned access policy decides the corridor in which profiles may operate.'),
            new ManagingFieldViewProfilePriorityRow(40, 'Role/group/user default profile', 'Administering', 'visible/hidden', 'system defaults only', 'Default profiles may shape presentation but cannot create access.'),
            new ManagingFieldViewProfilePriorityRow(50, 'User personal view profile', 'Managing', 'visible/hidden', 'allowed presentation', 'Personal preference can override visibility only for hideable and allowed fields.'),
            new ManagingFieldViewProfilePriorityRow(60, 'EasyAdmin field emission', 'Managing', 'render/not-render', 'none', 'EasyAdmin receives only the final allowed and visible field set.'),
        ];
    }

    public function ruleShapes(): array
    {
        return [
            new ManagingFieldViewProfileRuleShape(
                'subjects',
                'subjects.{subjectIdentifier}',
                'map',
                true,
                ['defaults', 'resources'],
                'Subject identifiers may be exact, such as user:42, or wildcard * for shared defaults.',
            ),
            new ManagingFieldViewProfileRuleShape(
                'defaults',
                'subjects.{subject}.defaults.{page}',
                'list',
                false,
                ['visible', 'hidden'],
                'Page may be index, detail, new, edit, all, or *.',
            ),
            new ManagingFieldViewProfileRuleShape(
                'resources',
                'subjects.{subject}.resources.{resourceClass}.{page}',
                'list',
                false,
                ['visible', 'hidden'],
                'Resource-specific page rules win over subject defaults.',
            ),
            new ManagingFieldViewProfileRuleShape(
                'visible',
                'visible: [fieldName]',
                'list',
                false,
                ['field names'],
                'Visible never grants access; denied fields still remain unavailable.',
            ),
            new ManagingFieldViewProfileRuleShape(
                'hidden',
                'hidden: [fieldName]',
                'list',
                false,
                ['field names'],
                'Required or non-hideable form fields must remain visible on new/edit.',
            ),
        ];
    }
}
