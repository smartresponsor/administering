<?php

declare(strict_types=1);

namespace App\Administering\Value\Form\Managing;

final class AdministrationManagingFieldViewProfileApplyData
{
    public string $normalizedProfilePayload = <<<'JSON'
{
  "subjects": {
    "user:42": {
      "defaults": {
        "index": {
          "hidden": [
            "createdAt"
          ]
        }
      }
    }
  }
}
JSON;

    public string $reviewContext = <<<'JSON'
{
  "surface": "managing_field_view_profile_review",
  "subject_key": "user:42",
  "profile_permission": "managing.field.profile.user_update",
  "mode": "replace",
  "page_name": "index"
}
JSON;

    public ?string $reason = null;
}
