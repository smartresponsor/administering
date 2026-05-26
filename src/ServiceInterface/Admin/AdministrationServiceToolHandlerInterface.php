<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Admin;

use App\Administering\Value\Admin\AdministrationServiceToolInvocation;
use App\Administering\Value\Operation\AdministrationOperationExecutionResult;

/**
 * Optional execution contract for openable Administering service tools.
 *
 * Tool existence is still discovered from src/Service/<Direction> naming rules.
 * Implementing this interface only means that a submitted tool form can be
 * executed by the generic service-tool operation dispatcher.
 */
interface AdministrationServiceToolHandlerInterface
{
    public function handleAdministrationServiceTool(AdministrationServiceToolInvocation $invocation): AdministrationOperationExecutionResult;
}
