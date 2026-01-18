<?php

namespace App\Service;

use App\Entity\Asset;
use App\Entity\Transaction;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

class LedgerService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TransactionRepository $transactionRepository
    ) {}

    /**
     * Reverses all transactions associated with a specific entity.
     * Used when an entity is updated or deleted.
     */
    public function reverseTransactions(string $relatedType, int $relatedId): void
    {
        $transactions = $this->transactionRepository->findBy([
            'relatedEntityType' => $relatedType,
            'relatedEntityId' => $relatedId
        ]);

        foreach ($transactions as $tx) {
            // Simply create a reversal transaction
            $reversalAmount = bcmul($tx->getAmount(), '-1', 2);
            $asset = $tx->getAsset();

            $campaign = $asset->getCampaign();
            $currentDay = $campaign?->getSessionDay() ?? 0;
            $currentYear = $campaign?->getSessionYear() ?? 0;

            $this->createTransaction(
                $asset,
                $reversalAmount,
                "CORRECTION: Reversing " . $tx->getDescription(),
                $currentDay,
                $currentYear,
                $relatedType,
                $relatedId
            );
        }
    }
    /**
     * process a deposit (credit) to the asset's account.
     */
    public function deposit(Asset $asset, string $amount, string $description, int $day, int $year, ?string $relatedType = null, ?int $relatedId = null, ?string $status = null): Transaction
    {
        return $this->createTransaction($asset, $amount, $description, $day, $year, $relatedType, $relatedId, $status);
    }

    /**
     * Process a withdrawal (debit) from the asset's account.
     * Amount should be positive, it will be negated internally.
     */
    public function withdraw(Asset $asset, string $amount, string $description, int $day, int $year, ?string $relatedType = null, ?int $relatedId = null, ?string $status = null): Transaction
    {
        if (!is_numeric($amount) || $amount <= 0) {
            throw new InvalidArgumentException("Withdrawal amount must be positive.");
        }

        $negativeAmount = bcmul($amount, '-1', 2);
        return $this->createTransaction($asset, $negativeAmount, $description, $day, $year, $relatedType, $relatedId, $status);
    }

    private function createTransaction(
        Asset $asset,
        string $amount,
        string $description,
        int $day,
        ?int $year,
        ?string $relatedType,
        ?int $relatedId,
        ?string $status = null
    ): Transaction {
        $transaction = new Transaction();
        $transaction->setAsset($asset);
        $transaction->setAmount($amount);
        $transaction->setDescription($description);
        $transaction->setSessionDay($day);
        $transaction->setSessionYear($year);
        $transaction->setRelatedEntityType($relatedType);
        $transaction->setRelatedEntityId($relatedId);

        if ($status !== null) {
            $transaction->setStatus($status);
        }

        $this->entityManager->persist($transaction);

        // CHRONOLOGICAL RULE:
        // Update balance ONLY if transaction date <= Campaign's Current Session Date
        // AND status is not VOID.
        // Status is set to POSTED if effective, PENDING otherwise.

        if ($transaction->getStatus() !== Transaction::STATUS_VOID) {
            if ($this->isEffective($asset, $day, $year)) {
                $transaction->setStatus(Transaction::STATUS_POSTED);

                $currentBalance = $asset->getCredits() ?? '0.00';
                $newBalance = bcadd($currentBalance, $amount, 2);
                $asset->setCredits($newBalance);
            } else {
                $transaction->setStatus(Transaction::STATUS_PENDING);
            }
        }

        $this->entityManager->flush();

        return $transaction;
    }

    private function isEffective(Asset $asset, int $day, int $year): bool
    {
        $campaign = $asset->getCampaign();

        if (!$campaign) {
            return true;
        }

        $currentDay = $campaign->getSessionDay();
        $currentYear = $campaign->getSessionYear();

        if ($currentDay === null || $currentYear === null) {
            return true;
        }

        // Compare Years
        if ($year < $currentYear) return true;
        if ($year > $currentYear) return false;

        // Same Year, Compare Days
        return $day <= $currentDay;
    }

    // Method to synchronize Ledger with Campaign Date (Time Travel)
    public function processCampaignSync(\App\Entity\Campaign $campaign): void
    {
        $currentDay = $campaign->getSessionDay() ?? 0;
        $currentYear = $campaign->getSessionYear() ?? 0;

        foreach ($campaign->getAssets() as $asset) {
            // 1. Forward Time: Find Pending transactions that are now effective
            $pending = $this->transactionRepository->findPendingEffective($asset, $currentDay, $currentYear);
            foreach ($pending as $tx) {
                // Apply funds
                $currentBalance = $asset->getCredits() ?? '0.00';
                $newBalance = bcadd($currentBalance, $tx->getAmount(), 2);
                $asset->setCredits($newBalance);

                // Mark as Posted
                $tx->setStatus(Transaction::STATUS_POSTED);
            }

            // 2. Backward Time: Find Posted transactions that are now in the future (Time Travel Undo)
            // If the user moves date back, we must reverted "Future" transactions to PENDING
            // and remove the funds.
            $postedFuture = $this->transactionRepository->findPostedFuture($asset, $currentDay, $currentYear);
            foreach ($postedFuture as $tx) {
                // Revert funds (subtract the amount)
                // Note: If original amount was negative (Cost), subtracting it ADDS funds back. Correct.
                $currentBalance = $asset->getCredits() ?? '0.00';
                $newBalance = bcsub($currentBalance, $tx->getAmount(), 2);
                $asset->setCredits($newBalance);

                // Mark back to Pending
                $tx->setStatus(Transaction::STATUS_PENDING);
            }
        }
    }
}
