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
     * Chiude l'anno fiscale per un asset.
     * 
     * Passaggi:
     * 1. Controlla se ci sono transazioni non postate nell'anno target (bloccante).
     * 2. Calcola il totale delle transazioni per quell'anno.
     * 3. Archivia transazioni.
     * 4. Rimuovi transazioni originali.
     * 5. Crea transazione "Saldo Iniziale" per Anno + 1.
     */
    public function closeFiscalYear(Asset $asset, int $year): void
    {
        // 1. Validazione
        $financialAccount = $asset->getFinancialAccount();
        if (!$financialAccount) {
            // Se non c'è conto, non ci sono transazioni.
            // O forse è un errore se stiamo chiudendo un anno?
            // Se non c'è conto, non ci sono transazioni da archiviare.
            throw new \RuntimeException("No financial account found for asset.");
        }

        $pendingQuery = $this->transactionRepository->createQueryBuilder('t')
            ->select('count(t.id)')
            ->where('t.financialAccount = :fa')
            ->andWhere('t.sessionYear = :year')
            ->andWhere('t.status != :status')
            ->setParameter('fa', $financialAccount)
            ->setParameter('year', $year)
            ->setParameter('status', Transaction::STATUS_POSTED)
            ->getQuery();

        if ($pendingQuery->getSingleScalarResult() > 0) {
            throw new \RuntimeException("Cannot close fiscal year $year: There are pending or void transactions.");
        }

        // 2. Recupera Transazioni da Archiviare
        $transactions = $this->transactionRepository->findBy([
            'financialAccount' => $financialAccount,
            'sessionYear' => $year
        ]);

        if (empty($transactions)) {
            // Nota: Se non ci sono transazioni, tecnicamente l'anno è "chiuso" o "vuoto".
            // Non lanciare eccezione bloccante permette chiusure idempotenti?
            // Ma il test si aspetta eccezione se vuoto?
            // "No transactions found" in originale.
            throw new \RuntimeException("No transactions found for fiscal year $year.");
        }

        $yearTotal = '0.00';
        $transactionCount = 0;

        foreach ($transactions as $transaction) {
            // Archivia
            $archive = TransactionArchive::fromTransaction($transaction);
            $this->em->persist($archive);

            // Somma
            $yearTotal = bcadd($yearTotal, $transaction->getAmount(), 2);

            // Cancella Originale
            $this->em->remove($transaction);
            $transactionCount++;
        }

        // 3. Creazione snapshot
        $nextYear = $year + 1;

        // 3. Crea Snapshot
        // Creiamo manualmente la transazione per evitare che LedgerService aggiunga nuovamente l'importo al saldo dell'Asset.
        // Il saldo dell'Asset è già corretto (rappresenta la somma delle transazioni archiviate).
        $nextYear = $year + 1;

        $snapshot = new Transaction();
        $snapshot->setFinancialAccount($financialAccount);
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
