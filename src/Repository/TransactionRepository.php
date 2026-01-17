<?php

namespace App\Repository;

use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }
    /**
     * Finds Pending transactions that have become effective (Date <= Current Date).
     * @return Transaction[]
     */
    public function findPendingEffective(\App\Entity\Ship $ship, int $currentDay, int $currentYear): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.ship = :ship')
            ->andWhere('t.status = :status')
            ->andWhere('(t.sessionYear < :year) OR (t.sessionYear = :year AND t.sessionDay <= :day)')
            ->setParameter('ship', $ship)
            ->setParameter('status', Transaction::STATUS_PENDING)
            ->setParameter('year', $currentYear)
            ->setParameter('day', $currentDay)
            ->getQuery()
            ->getResult();
    }

    /**
     * Finds Posted transactions that are now in the future (Date > Current Date).
     * Used for undoing time travel (backtracking).
     * @return Transaction[]
     */
    public function findPostedFuture(\App\Entity\Ship $ship, int $currentDay, int $currentYear): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.ship = :ship')
            ->andWhere('t.status = :status')
            ->andWhere('(t.sessionYear > :year) OR (t.sessionYear = :year AND t.sessionDay > :day)')
            ->setParameter('ship', $ship)
            ->setParameter('status', Transaction::STATUS_POSTED)
            ->setParameter('year', $currentYear)
            ->setParameter('day', $currentDay)
            ->getQuery()
            ->getResult();
    }
}
