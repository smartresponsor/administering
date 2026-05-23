<?php

declare(strict_types=1);

namespace App\Administering\Value\Form\Managing;

use App\Administering\Value\Rolling\AdministrationManagingFieldPermissionVocabulary;

final class AdministrationFieldAccessMutationReviewData
{
    public string $subjectType = 'role';
    public string $subjectIdentifier = '';
    public string $effect = 'allow';
    public string $permissionKey = AdministrationManagingFieldPermissionVocabulary::FIELD_VIEW;
    public string $resourceClass = 'App\\Cataloging\\Entity\\Catalog\\CatalogCategoryEntity';
    public string $fieldName = 'title';
    public string $pageName = 'detail';
    public string $operation = 'view';
    public ?string $reason = null;
}
