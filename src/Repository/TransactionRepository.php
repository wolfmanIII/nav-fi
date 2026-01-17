<?php

namespace App\Repository;

use App\Entity\Transaction;
use App\Entity\Asset;
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
    public function findPendingEffective(Asset $asset, int $currentDay, int $currentYear): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.asset = :asset')
            ->andWhere('t.status = :status')
            ->andWhere('(t.sessionYear < :year) OR (t.sessionYear = :year AND t.sessionDay <= :day)')
            ->setParameter('asset', $asset)
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
    public function findPostedFuture(Asset $asset, int $currentDay, int $currentYear): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.asset = :asset')
            ->andWhere('t.status = :status')
            ->andWhere('(t.sessionYear > :year) OR (t.sessionYear = :year AND t.sessionDay > :day)')
            ->setParameter('asset', $asset)
            ->setParameter('status', Transaction::STATUS_POSTED)
            ->setParameter('year', $currentYear)
            ->setParameter('day', $currentDay)
            ->getQuery()
            ->getResult();
    }

    /**
     * Finds all transactions for an asset with pagination support.
     * @param Asset $asset
     * @param int $page
     * @param int $limit
     * @return array{'items': Transaction[], 'total': int}
     */
    public function findForAsset(Asset $asset, int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.asset = :asset')
            ->setParameter('asset', $asset)
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
