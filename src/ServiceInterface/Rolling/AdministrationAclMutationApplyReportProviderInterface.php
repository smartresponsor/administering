<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Rolling;

use App\Administering\Entity\AdministrationAclMutationApplyRecord;
use App\Managing\Value\Administration\ManagingAclMutationApplySummary;

/**
 * Provides metadata-only reports for Rolling ACL apply attempts persisted by Administering.
 */
interface AdministrationAclMutationApplyReportProviderInterface
{
    /** @return list<AdministrationAclMutationApplyRecord> */
    public function recent(int $limit = 50): array;

    public function summary(int $limit = 200): ManagingAclMutationApplySummary;
}
