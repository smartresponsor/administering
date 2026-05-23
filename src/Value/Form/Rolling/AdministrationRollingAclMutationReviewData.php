<?php

declare(strict_types=1);

namespace App\Administering\Value\Form\Rolling;

final class AdministrationRollingAclMutationReviewData
{
    public string $mutationType = 'permission.grant';
    public string $subjectIdentifier = '';
    public string $permissionOrRoleKey = 'managing.field.view';
    public string $componentKey = 'managing';
    public string $resourceClass = 'App\\Cataloging\\Entity\\Catalog\\CatalogCategoryEntity';
    public string $pageName = 'detail';
    public string $fieldName = 'title';
}
