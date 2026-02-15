<?php

namespace App\Repository;

use App\Entity\Transaction;
use App\Entity\Asset;
use App\Entity\FinancialAccount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
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
    public function findPendingEffective(FinancialAccount $account, int $currentDay, int $currentYear): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.financialAccount = :account')
            ->andWhere('t.status = :status')
            ->andWhere('(t.sessionYear < :year) OR (t.sessionYear = :year AND t.sessionDay <= :day)')
            ->setParameter('account', $account)
            ->setParameter('status', Transaction::STATUS_PENDING)
            ->setParameter('year', $currentYear)
            ->setParameter('day', $currentDay)
            ->getQuery()
            ->getResult();
    }

    public function findPostedFuture(FinancialAccount $account, int $currentDay, int $currentYear): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.financialAccount = :account')
            ->andWhere('t.status = :status')
            ->andWhere('(t.sessionYear > :year) OR (t.sessionYear = :year AND t.sessionDay > :day)')
            ->setParameter('account', $account)
            ->setParameter('status', Transaction::STATUS_POSTED)
            ->setParameter('year', $currentYear)
            ->setParameter('day', $currentDay)
            ->getQuery()
            ->getResult();
    }

    public function findForAccount(FinancialAccount $account, int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.financialAccount = :account')
            ->setParameter('account', $account)
            ->orderBy('t.sessionYear', 'DESC')
            ->addOrderBy('t.sessionDay', 'DESC')
            ->addOrderBy('t.createdAt', 'DESC')
            ->addOrderBy('t.id', 'DESC');

        $query = $qb->getQuery();

        $paginator = new Paginator($query);
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
