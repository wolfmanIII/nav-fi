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

    /**
     * Finds all transactions for a ship with pagination support.
     * @param \App\Entity\Ship $ship
     * @param int $page
     * @param int $limit
     * @return array{'items': Transaction[], 'total': int}
     */
    public function findForShip(\App\Entity\Ship $ship, int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.ship = :ship')
            ->setParameter('ship', $ship)
            ->orderBy('t.sessionYear', 'DESC')
            ->addOrderBy('t.sessionDay', 'DESC')
            ->addOrderBy('t.createdAt', 'DESC')
            ->addOrderBy('t.id', 'DESC');

        $query = $qb->getQuery();

        $paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($query);
        $total = count($paginator);

        $paginator->getQuery()
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return [
            'items' => iterator_to_array($paginator),
            'total' => $total,
        ];
    }
}
