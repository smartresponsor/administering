<?php

declare(strict_types=1);

namespace App\Administering\Recorder\Accessing;

use App\Administering\Entity\AdministrationAccountActionRequestRecordEntity;
use App\Administering\Repository\AdministrationPersistenceRepository;
use App\Administering\ServiceInterface\Accessing\AdministrationAccountActionRequestRecorderInterface;
use App\Administering\ServiceInterface\Audit\AdministrationAuditRecorderInterface;
use App\Administering\Value\Accessing\AdministrationAccountActionRequest;
use App\Administering\Value\Accessing\AdministrationAccountActionResult;

final readonly class AdministrationDoctrineAccountActionRequestRecorder implements AdministrationAccountActionRequestRecorderInterface
{
    public function __construct(
        private AdministrationPersistenceRepository $persistenceRepository,
        private AdministrationAuditRecorderInterface $auditRecorder,
    ) {
    }

    public function record(
        AdministrationAccountActionRequest $request,
        AdministrationAccountActionResult $result,
    ): AdministrationAccountActionRequestRecordEntity {
        $record = new AdministrationAccountActionRequestRecordEntity(
            sprintf('account-action-%s', bin2hex(random_bytes(8))),
            $request->action(),
            $request->accountReference(),
            $request->requestedBySubject(),
            $result->status(),
            $request->safeReason(),
            $result->safeMessage(),
            $result->safeContext() + [
                'source' => 'administering_ui',
                'request_context' => $request->safeContext(),
            ],
        );

        $this->persistenceRepository->persist($record);

        $this->auditRecorder->record('administration.accessing.account_action.requested', $request->requestedBySubject(), [
            'request_key' => $record->requestKey(),
            'action' => $record->action(),
            'account_reference' => $record->accountReference(),
            'status' => $record->status(),
        ]);

        return $record;
    }
}
