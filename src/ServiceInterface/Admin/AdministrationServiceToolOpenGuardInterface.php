<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Admin;

use App\Administering\Entity\AdministrationServiceToolRecord;
use App\Administering\Value\Admin\AdministrationServiceToolInvocation;

/**
 * Guards the open/execute boundary for materialized service-tool records.
 *
 * Internal Administering tools and owner-provided configuration tools share the
 * same EasyRuntime Scope source, but they must prove different provenance metadata
 * before Administering renders a form or dispatches a persisted operation run.
 */
interface AdministrationServiceToolOpenGuardInterface
{
    public function assertRecordCanOpen(AdministrationServiceToolRecord $record): void;

    public function assertInvocationCanExecute(AdministrationServiceToolInvocation $invocation): void;
}
