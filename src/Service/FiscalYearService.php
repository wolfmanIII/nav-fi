<?php

namespace App\Service;

use App\Entity\Asset;
use App\Entity\Transaction;
use App\Entity\TransactionArchive;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;

class FiscalYearService
{
    public function __construct(
        private EntityManagerInterface $em,
        private TransactionRepository $transactionRepository,
        private LedgerService $ledgerService
    ) {}

    /**
     * Closes the fiscal year for an asset.
     * 
     * Steps:
     * 1. Check if there are unposted transactions in the target year (blocker).
     * 2. Calculate total of transactions for that year.
     * 3. Archive transactions.
     * 4. Remove original transactions.
     * 5. Create "Initial Balance" transaction for Year + 1.
     */
    public function closeFiscalYear(Asset $asset, int $year): void
    {
        // 1. Validation
        $pendingQuery = $this->transactionRepository->createQueryBuilder('t')
            ->select('count(t.id)')
            ->where('t.asset = :asset')
            ->andWhere('t.sessionYear = :year')
            ->andWhere('t.status != :status')
            ->setParameter('asset', $asset)
            ->setParameter('year', $year)
            ->setParameter('status', Transaction::STATUS_POSTED)
            ->getQuery();

        if ($pendingQuery->getSingleScalarResult() > 0) {
            throw new \RuntimeException("Cannot close fiscal year $year: There are pending or void transactions.");
        }

        // 2. Fetch Transactions to Archive
        $transactions = $this->transactionRepository->findBy([
            'asset' => $asset,
            'sessionYear' => $year
        ]);

        if (empty($transactions)) {
            throw new \RuntimeException("No transactions found for fiscal year $year.");
        }

        $yearTotal = '0.00';
        $transactionCount = 0;

        foreach ($transactions as $transaction) {
            // Archive
            $archive = TransactionArchive::fromTransaction($transaction);
            $this->em->persist($archive);

            // Sum
            $yearTotal = bcadd($yearTotal, $transaction->getAmount(), 2);

            // Delete Original
            $this->em->remove($transaction);
            $transactionCount++;
        }

        // 3. Create Snapshot
        $nextYear = $year + 1;

        // 3. Create Snapshot
        // We manually create the transaction to avoid LedgerService adding the amount to the Asset balance again.
        // The Asset balance is already correct (it represents the sum of the archived transactions).
        $nextYear = $year + 1;

        $snapshot = new Transaction();
        $snapshot->setAsset($asset);
        $snapshot->setAmount($yearTotal);
        $snapshot->setDescription("Rendiconto Iniziale Anno $nextYear (Snapshot $year)");
        $snapshot->setSessionDay(1);
        $snapshot->setSessionYear($nextYear);
        $snapshot->setRelatedEntityType('SNAPSHOT');
        $snapshot->setRelatedEntityId($year);
        $snapshot->setStatus(Transaction::STATUS_POSTED);

        $this->em->persist($snapshot);
        $this->em->flush();
    }
}
