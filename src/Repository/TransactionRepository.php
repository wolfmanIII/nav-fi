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
     * Trova transazioni Pending che sono diventate effettive (Data <= Data Corrente).
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
     * Trova transazioni Posted che sono ora nel futuro (Data > Data Corrente).
     * Usato per annullare il viaggio nel tempo (backtracking).
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
     * Trova tutte le transazioni per un asset con supporto paginazione.
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
