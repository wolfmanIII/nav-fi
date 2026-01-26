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
     * Inverte tutte le transazioni associate a una specifica entità.
     * Usato quando un'entità viene aggiornata o cancellata.
     * @return Transaction[]
     */
    public function reverseTransactions(string $relatedType, int $relatedId, bool $autoFlush = true): array
    {
        $transactions = $this->transactionRepository->findBy([
            'relatedEntityType' => $relatedType,
            'relatedEntityId' => $relatedId
        ]);

        $created = [];
        foreach ($transactions as $tx) {
            // Crea semplicemente una transazione di inversione
            $reversalAmount = bcmul($tx->getAmount(), '-1', 2);
            $financialAccount = $tx->getFinancialAccount();
            $asset = $financialAccount?->getAsset();

            if (!$asset) {
                // If asset is gone, we can't easily reverse in context of a campaign?
                // Or we just skip? For safety let's skip or log.
                continue;
            }

            $campaign = $asset->getCampaign();
            $currentDay = $campaign?->getSessionDay() ?? 0;
            $currentYear = $campaign?->getSessionYear() ?? 0;

            $created[] = $this->createTransaction(
                $asset,
                $reversalAmount,
                "REVERSAL: " . $tx->getDescription(),
                $currentDay,
                $currentYear,
                $relatedType,
                $relatedId,
                null,
                $autoFlush
            );
        }
        return $created;
    }
    /**
     * processa un deposito (accredito) sul conto dell'asset.
     */
    public function deposit(Asset $asset, string $amount, string $description, int $day, int $year, ?string $relatedType = null, ?int $relatedId = null, ?string $status = null, bool $autoFlush = true): Transaction
    {
        return $this->createTransaction($asset, $amount, $description, $day, $year, $relatedType, $relatedId, $status, $autoFlush);
    }

    /**
     * Processa un prelievo (addebito) dal conto dell'asset.
     * L'importo deve essere positivo, verrà negato internamente.
     */
    public function withdraw(Asset $asset, string $amount, string $description, int $day, int $year, ?string $relatedType = null, ?int $relatedId = null, ?string $status = null, bool $autoFlush = true): Transaction
    {
        if (!is_numeric($amount) || $amount <= 0) {
            throw new InvalidArgumentException("Withdrawal amount must be positive.");
        }

        $negativeAmount = bcmul($amount, '-1', 2);
        return $this->createTransaction($asset, $negativeAmount, $description, $day, $year, $relatedType, $relatedId, $status, $autoFlush);
    }

    private function createTransaction(
        Asset $asset,
        string $amount,
        string $description,
        int $day,
        ?int $year,
        ?string $relatedType,
        ?int $relatedId,
        ?string $status = null,
        bool $autoFlush = true
    ): Transaction {
        $transaction = new Transaction();

        $financialAccount = $asset->getFinancialAccount();
        if (!$financialAccount) {
            // Auto-create or throw? Throwing is safer as Asset should have it.
            // Or create one on the fly?
            $financialAccount = new \App\Entity\FinancialAccount();
            $financialAccount->setUser($asset->getUser());
            $financialAccount->setAsset($asset);
            $this->entityManager->persist($financialAccount);
            // We need to flush financial account if we want to use it? 
            // setFinancialAccount works with object pending persist.
        }

        $transaction->setFinancialAccount($financialAccount);
        $transaction->setAmount($amount);
        $transaction->setDescription($description);
        $transaction->setSessionDay($day);
        $transaction->setSessionYear($year);
        $transaction->setRelatedEntityType($relatedType);
        $transaction->setRelatedEntityId($relatedId);

        if ($status !== null) {
            $transaction->setStatus($status);
        }

        // REGOLA CRONOLOGICA:
        // Aggiorna saldo SOLO se data transazione <= Data Sessione Corrente Campagna
        // E lo stato non è VOID.
        // Lo stato è impostato su POSTED se effettivo, PENDING altrimenti.

        if ($transaction->getStatus() !== Transaction::STATUS_VOID) {
            if ($this->isEffective($asset, $day, $year)) {
                $transaction->setStatus(Transaction::STATUS_POSTED);

                $financialAccount = $asset->getFinancialAccount();
                if ($financialAccount) {
                    $currentBalance = $financialAccount->getCredits() ?? '0.00';
                    $newBalance = bcadd($currentBalance, $amount, 2);
                    $financialAccount->setCredits($newBalance);
                }
            } elseif ($transaction->getStatus() !== Transaction::STATUS_VOID) {
                // Imposta su pending solo se non void (controllo ridondante ma sicuro)
                $transaction->setStatus(Transaction::STATUS_PENDING);
            }
        }

        $this->entityManager->persist($transaction);
        if ($autoFlush) {
            $this->entityManager->flush();
        }

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

        // Confronta anni
        if ($year < $currentYear) return true;
        if ($year > $currentYear) return false;

        // Stesso anno, confronta giorni
        return $day <= $currentDay;
    }

    // Metodo per sincronizzare il Libro Mastro con la Data Campagna (Viaggio nel Tempo)
    public function processCampaignSync(\App\Entity\Campaign $campaign): void
    {
        $currentDay = $campaign->getSessionDay() ?? 0;
        $currentYear = $campaign->getSessionYear() ?? 0;

        foreach ($campaign->getAssets() as $asset) {
            // 1. Avanti nel Tempo: Trova transazioni Pending che sono ora effettive
            $financialAccount = $asset->getFinancialAccount();

            if ($financialAccount) {
                // 1. Avanti nel Tempo: Trova transazioni Pending che sono ora effettive
                $pending = $this->transactionRepository->findPendingEffective($financialAccount, $currentDay, $currentYear);

                foreach ($pending as $tx) {
                    // Applica fondi
                    $currentBalance = $financialAccount->getCredits() ?? '0.00';
                    $newBalance = bcadd($currentBalance, $tx->getAmount(), 2);
                    $financialAccount->setCredits($newBalance);

                    // Segna come Posted
                    $tx->setStatus(Transaction::STATUS_POSTED);
                }

                // 2. Indietro nel Tempo: Trova transazioni Posted che sono ora nel futuro (Annulla Viaggio nel Tempo)
                // Se l'utente sposta la data indietro, dobbiamo riportare le transazioni "Future" a PENDING
                // e rimuovere i fondi.
                $postedFuture = $this->transactionRepository->findPostedFuture($financialAccount, $currentDay, $currentYear);
                foreach ($postedFuture as $tx) {
                    // Inverti fondi (sottrai l'importo)
                    $currentBalance = $financialAccount->getCredits() ?? '0.00';
                    $newBalance = bcsub($currentBalance, $tx->getAmount(), 2);
                    $financialAccount->setCredits($newBalance);

                    // Segna di nuovo come Pending
                    $tx->setStatus(Transaction::STATUS_PENDING);
                }
            }
        }
    }
}
