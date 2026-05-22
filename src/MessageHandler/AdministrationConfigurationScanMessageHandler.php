<?php

declare(strict_types=1);

namespace App\Administering\MessageHandler;

use App\Administering\Message\AdministrationConfigurationScanMessage;
use App\Administering\ServiceInterface\Configuration\AdministrationConfigurationScannerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class AdministrationConfigurationScanMessageHandler
{
    public function __construct(private AdministrationConfigurationScannerInterface $scanner)
    {
    }

    public function __invoke(AdministrationConfigurationScanMessage $message): void
    {
        $this->scanner->scan($message->hostRoot());
    }
}
