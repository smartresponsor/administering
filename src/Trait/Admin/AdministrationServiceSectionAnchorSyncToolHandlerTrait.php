<?php

declare(strict_types=1);

namespace App\Administering\Trait\Admin;

use App\Administering\Value\Admin\AdministrationServiceSectionAnchorSyncResult;
use App\Administering\Value\Admin\AdministrationServiceToolInvocation;
use App\Administering\Value\Operation\AdministrationOperationExecutionResult;

/**
 * Bridges section-anchor sync services into the generic service-tool execution flow.
 *
 * The sync service remains the concrete owner of synchronize(); this trait only
 * converts its result into the safe operation result expected by the dispatcher.
 */
trait AdministrationServiceSectionAnchorSyncToolHandlerTrait
{
    abstract public function synchronize(): AdministrationServiceSectionAnchorSyncResult;

    public function handleAdministrationServiceTool(AdministrationServiceToolInvocation $invocation): AdministrationOperationExecutionResult
    {
        $result = $this->synchronize();

        return AdministrationOperationExecutionResult::succeeded(
            sprintf('Synchronized %s anchor records.', $result->sectionKey),
            [
                'tool_key' => $invocation->toolKey,
                'section_key' => $invocation->sectionKey,
                'tool_slug' => $invocation->toolSlug,
                'sync_section_key' => $result->sectionKey,
                'sync_status' => $result->status,
                'record_count' => $result->recordCount,
                'messages' => $result->messages,
            ],
        );
    }
}
