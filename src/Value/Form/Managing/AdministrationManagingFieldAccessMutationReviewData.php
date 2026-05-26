<?php

declare(strict_types=1);

namespace App\Administering\Value\Form\Managing;

use App\Managing\Value\Administration\ManagingFieldPermissionVocabulary;

final class AdministrationManagingFieldAccessMutationReviewData
{
    public string $subjectType = 'role';
    public string $subjectIdentifier = '';
    public string $effect = 'allow';
    public string $permissionKey = ManagingFieldPermissionVocabulary::FIELD_VIEW;
    public string $resourceClass = 'App\\Cataloging\\Entity\\Catalog\\CatalogCategoryEntity';
    public string $fieldName = 'title';
    public string $pageName = 'detail';
    public string $operation = 'view';
    public ?string $reason = null;
}
