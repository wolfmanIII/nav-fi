<?php

namespace App\Repository;

use App\Entity\Cost;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cost>
 */
class CostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cost::class);
    }

    /**
     * @return Cost[]
     */
    public function findAllForUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.paymentYear', 'DESC')
            ->addOrderBy('c.paymentDay', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findOneForUser(int $id, User $user): ?Cost
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.id = :id')
            ->andWhere('c.user = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @param array{title?: string, category?: int, asset?: int, campaign?: int} $filters
     *
     * @return array{items: Cost[], total: int}
     */
    public function findForUserWithFilters(User $user, array $filters, int $page, int $limit): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.costCategory', 'cat')
            ->leftJoin('c.financialAccount', 'fa')
            ->leftJoin('fa.asset', 'a')
            ->leftJoin('a.campaign', 'camp')
            ->addSelect('cat', 'a', 'camp')
            ->andWhere('c.user = :user')
            ->setParameter('user', $user);

        if (!empty($filters['title'])) {
            $title = '%' . strtolower($filters['title']) . '%';
            $qb->andWhere('LOWER(c.title) LIKE :title')
                ->setParameter('title', $title);
        }

        if ($filters['category'] !== null) {
            $qb->andWhere('cat.id = :category')
                ->setParameter('category', (int) $filters['category']);
        }

        if ($filters['asset'] !== null) {
            $qb->andWhere('a.id = :asset')
                ->setParameter('asset', (int) $filters['asset']);
        }

        if ($filters['campaign'] !== null) {
            $qb->andWhere('camp.id = :campaign')
                ->setParameter('campaign', (int) $filters['campaign']);
        }

        $qb->orderBy('c.paymentYear', 'DESC')
            ->addOrderBy('c.paymentDay', 'DESC');

        $query = $qb->getQuery()
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $paginator = new Paginator($query);

        return [
            'items' => iterator_to_array($paginator),
            'total' => $paginator->count(),
        ];
    }
    public function findUnsoldTradeGoods(User $user): array
    {
        // Cerchiamo i costi TRADE che non sono ancora stati collegati a un Income (liquidazione)
        return $this->createQueryBuilder('c')
            ->join('c.costCategory', 'cat')
            ->leftJoin('App\Entity\Income', 'inc', 'WITH', 'inc.purchaseCost = c')
            ->andWhere('c.user = :user')
            ->andWhere('cat.code = :tradeCode')
            ->andWhere('inc.id IS NULL')
            ->setParameter('user', $user)
            ->setParameter('tradeCode', 'TRADE')
            ->orderBy('c.paymentYear', 'DESC')
            ->addOrderBy('c.paymentDay', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findUnsoldTradeCargoForAccount(\App\Entity\FinancialAccount $account): array
    {
        // Similar to findUnsoldTradeGoods but filtered by FinancialAccount
        return $this->createQueryBuilder('c')
            ->join('c.costCategory', 'cat')
            ->leftJoin('App\Entity\Income', 'inc', 'WITH', 'inc.purchaseCost = c')
            ->andWhere('c.financialAccount = :account')
            ->andWhere('cat.code = :tradeCode')
            ->andWhere('inc.id IS NULL')
            ->setParameter('account', $account)
            ->setParameter('tradeCode', 'TRADE')
            ->orderBy('c.paymentYear', 'DESC')
            ->addOrderBy('c.paymentDay', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
