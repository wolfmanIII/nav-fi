<?php

namespace App\EventSubscriber;

use App\Entity\Campaign;
use App\Service\LedgerService;
use App\Service\FinancialAutomationService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\PostUpdateEventArgs;

#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: Campaign::class)]
class CampaignLifecycleSubscriber
{
    public function __construct(
        private LedgerService $ledgerService,
        private FinancialAutomationService $financialAutomationService
    ) {}

    public function postUpdate(Campaign $campaign, PostUpdateEventArgs $event): void
    {
        // Quando la campagna viene aggiornata (probabile cambio data sessione), sincronizza il libro mastro.

        // 1. Genera nuovi elementi (costi ricorrenti)
        $this->financialAutomationService->processAutomatedFinancials($campaign);

        // 2. Sincronizza elementi esistenti/nuovi in base alla data (logica di viaggio nel tempo)
        $this->ledgerService->processCampaignSync($campaign);

        // 3. Flush delle modifiche (crediti asset, transazioni, rate)
        // Sicuro a patto di non aver modificato la Campaign stessa.
        $event->getObjectManager()->flush();
    }
}
