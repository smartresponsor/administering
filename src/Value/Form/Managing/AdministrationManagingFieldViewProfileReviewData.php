<?php

declare(strict_types=1);

namespace App\Administering\Value\Form\Managing;

final class AdministrationManagingFieldViewProfileReviewData
{
    public string $subjectType = 'user';
    public string $subjectIdentifier = '';
    public string $mode = 'replace';
    public string $resourceClass = '';
    public string $pageName = 'index';
    public string $visibleFields = '';
    public string $hiddenFields = '';
    public ?string $reason = null;
}
