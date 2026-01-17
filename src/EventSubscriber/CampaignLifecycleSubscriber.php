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
        // When campaign is updated (likely session date change), sync the ledger.

        // 1. Generate new items (Recurring Costs)
        $this->financialAutomationService->processAutomatedFinancials($campaign);

        // 2. Sync existing/new items based on date (Time Travel logic)
        $this->ledgerService->processCampaignSync($campaign);

        // 3. Flush changes (Asset credits, Transactions, Installments)
        // Safe provided we didn't modify Campaign itself.
        $event->getObjectManager()->flush();
    }
}
