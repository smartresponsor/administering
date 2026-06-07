<?php

declare(strict_types=1);

namespace App\Administering\Recorder\Accessing;

use App\Administering\Entity\AdministrationAccountActionRequestRecord;
use App\Administering\ServiceInterface\Accessing\AdministrationAccountActionRequestRecorderInterface;
use App\Administering\ServiceInterface\Audit\AdministrationAuditRecorderInterface;
use App\Administering\Value\Accessing\AdministrationAccountActionRequest;
use App\Administering\Value\Accessing\AdministrationAccountActionResult;
use Doctrine\Persistence\ManagerRegistry;

final readonly class DoctrineAdministrationAccountActionRequestRecorder implements AdministrationAccountActionRequestRecorderInterface
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private AdministrationAuditRecorderInterface $auditRecorder,
    ) {
    }

    public function record(
        AdministrationAccountActionRequest $request,
        AdministrationAccountActionResult $result,
    ): AdministrationAccountActionRequestRecord {
        $record = new AdministrationAccountActionRequestRecord(
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

        $manager = $this->manager();
        $manager->persist($record);
        $manager->flush();

        $this->auditRecorder->record('administration.accessing.account_action.requested', $request->requestedBySubject(), [
            'request_key' => $record->requestKey(),
            'action' => $record->action(),
            'account_reference' => $record->accountReference(),
            'status' => $record->status(),
        ]);

        return $record;
    }

    private function manager(): \Doctrine\Persistence\ObjectManager
    {
        $manager = $this->managerRegistry->getManagerForClass(AdministrationAccountActionRequestRecord::class);

        if (null === $manager) {
            throw new \LogicException('No Doctrine manager is configured for Administering account action records. Configure the system SQLite entity manager for App\\Administering entities.');
        }

        return $manager;
    }
}
