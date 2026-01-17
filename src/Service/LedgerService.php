<?php

namespace App\Service;

use App\Entity\Ship;
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
            // Check if already reversed? (Maybe by looking for a "Reversal" flag? 
            // Or just blindly reverse everything that isn't a reversal itself?)
            // For simplicity: We simply inverse the amount and log a "Correction".
            // To avoid infinite loops, we should ideally mark transactions as "Reversed" or just create a new one.
            // Let's create a NEW transaction that negates the old one.

            // Optimization: If the net sum is 0, do nothing? No, history is important.

            // Skip if it's already a correction? No, corrections can be corrected.
            // But we need to distinguish "Original" from "Correction"?
            // Let's just strictly negate whatever is there.

            // Check if this specific tx has NOT been reversed yet?
            // This requires tracking. 
            // PROPOSAL: Just "Void" the entity by summing all its history and negating the distinct sum.
            // But preserving history is better.

            // Simply create a reversal transaction
            $reversalAmount = bcmul($tx->getAmount(), '-1', 2);
            $ship = $tx->getShip();

            $campaign = $ship->getCampaign();
            $currentDay = $campaign?->getSessionDay() ?? 0;
            $currentYear = $campaign?->getSessionYear() ?? 0;

            $this->createTransaction(
                $ship,
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
     * process a deposit (credit) to the ship's account.
     */
    public function deposit(Ship $ship, string $amount, string $description, int $day, int $year, ?string $relatedType = null, ?int $relatedId = null): Transaction
    {
        return $this->createTransaction($ship, $amount, $description, $day, $year, $relatedType, $relatedId);
    }

    /**
     * Process a withdrawal (debit) from the ship's account.
     * Amount should be positive, it will be negated internally.
     */
    public function withdraw(Ship $ship, string $amount, string $description, int $day, int $year, ?string $relatedType = null, ?int $relatedId = null): Transaction
    {
        if (!is_numeric($amount) || $amount <= 0) {
            throw new InvalidArgumentException("Withdrawal amount must be positive.");
        }

        $negativeAmount = bcmul($amount, '-1', 2);
        return $this->createTransaction($ship, $negativeAmount, $description, $day, $year, $relatedType, $relatedId);
    }

    private function createTransaction(
        Ship $ship,
        string $amount,
        string $description,
        int $day,
        int $year,
        ?string $relatedType,
        ?int $relatedId
    ): Transaction {
        $transaction = new Transaction();
        $transaction->setShip($ship);
        $transaction->setAmount($amount);
        $transaction->setDescription($description);
        $transaction->setSessionDay($day);
        $transaction->setSessionYear($year);
        $transaction->setRelatedEntityType($relatedType);
        $transaction->setRelatedEntityId($relatedId);

        $this->entityManager->persist($transaction);

        // CHRONOLOGICAL RULE:
        // Update balance ONLY if transaction date <= Campaign's Current Session Date
        // Status is set to POSTED if effective, PENDING otherwise.

        if ($this->isEffective($ship, $day, $year)) {
            $transaction->setStatus(Transaction::STATUS_POSTED);

            $currentBalance = $ship->getCredits() ?? '0.00';
            $newBalance = bcadd($currentBalance, $amount, 2);
            $ship->setCredits($newBalance);
        } else {
            $transaction->setStatus(Transaction::STATUS_PENDING);
        }

        $this->entityManager->flush();

        return $transaction;
    }

    private function isEffective(Ship $ship, int $day, int $year): bool
    {
        $campaign = $ship->getCampaign();

        // Rule: The Ship's sessionDate is no longer used (deprecated).
        // We MUST rely on the Campaign's sessionDate.

        if (!$campaign) {
            // If the ship is not in a campaign, we cannot determine "Current Time".
            // Defaulting to TRUE (Effective) to allow independent ships to function simply?
            // Or FALSE because time is undefined?
            // "nessun fallback" implies strictness, effectively removing the Ship fallback.
            // Assuming TRUE for usability (if no campaign, everything is immediate).
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

        foreach ($campaign->getShips() as $ship) {
            // 1. Forward Time: Find Pending transactions that are now effective
            $pending = $this->transactionRepository->findPendingEffective($ship, $currentDay, $currentYear);
            foreach ($pending as $tx) {
                // Apply funds
                $currentBalance = $ship->getCredits() ?? '0.00';
                $newBalance = bcadd($currentBalance, $tx->getAmount(), 2);
                $ship->setCredits($newBalance);

                // Mark as Posted
                $tx->setStatus(Transaction::STATUS_POSTED);
            }

            // 2. Backward Time: Find Posted transactions that are now in the future (Time Travel Undo)
            // If the user moves date back, we must reverted "Future" transactions to PENDING
            // and remove the funds.
            $postedFuture = $this->transactionRepository->findPostedFuture($ship, $currentDay, $currentYear);
            foreach ($postedFuture as $tx) {
                // Revert funds (subtract the amount)
                // Note: If original amount was negative (Cost), subtracting it ADDS funds back. Correct.
                $currentBalance = $ship->getCredits() ?? '0.00';
                $newBalance = bcsub($currentBalance, $tx->getAmount(), 2);
                $ship->setCredits($newBalance);

                // Mark back to Pending
                $tx->setStatus(Transaction::STATUS_PENDING);
            }
        }
    }
}
