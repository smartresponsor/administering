<?php

declare(strict_types=1);

namespace App\Administering\Value\Form\Managing;

final class AdministrationFieldVisibilityInspectionPrepareData
{
    public string $resourceClass = 'App\\Cataloging\\Entity\\Catalog\\CatalogCategoryEntity';
    public string $fieldName = 'title';
    public string $pageName = 'index';
    public string $subjectIdentifier = '';
    public string $statusCandidates = '';
    public string $publicationFlagCandidates = '';
    public string $publicationDateCandidates = '';
    public ?string $reason = null;
}
