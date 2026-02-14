<?php

namespace App\Repository;

use App\Entity\AnnualBudget;
use App\Entity\User;
use App\Service\ImperialDateHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AnnualBudget>
 */
class AnnualBudgetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private readonly ImperialDateHelper $dateHelper)
    {
        parent::__construct($registry, AnnualBudget::class);
    }

    /**
     * @return AnnualBudget[]
     */
    public function findAllForUser(User $user): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.user = :user')
            ->setParameter('user', $user)
            ->orderBy('b.startYear', 'DESC')
            ->addOrderBy('b.startDay', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneForUser(int $id, User $user): ?AnnualBudget
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.id = :id')
            ->andWhere('b.user = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param array{asset?: int, start?: string, end?: string, campaign?: int} $filters
     *
     * @return array{items: AnnualBudget[], total: int}
     */
    public function findForUserWithFilters(User $user, array $filters, int $page, int $limit): array
    {
        $qb = $this->createQueryBuilder('b')
            ->leftJoin('b.financialAccount', 'fa')
            ->leftJoin('fa.asset', 'a')
            ->leftJoin('a.campaign', 'c')
            ->addSelect('fa', 'a', 'c')
            ->andWhere('b.user = :user')
            ->setParameter('user', $user);

        if ($filters['asset'] !== null) {
            $qb->andWhere('a.id = :asset')
                ->setParameter('asset', (int) $filters['asset']);
        }

        $startKey = $this->dateHelper->parseFilter($filters['start'] ?? '', false);
        if ($startKey !== null) {
            $qb->andWhere('(b.startYear * 1000 + b.startDay) >= :startKey')
                ->setParameter('startKey', $startKey);
        }

        $endKey = $this->dateHelper->parseFilter($filters['end'] ?? '', true);
        if ($endKey !== null) {
            $qb->andWhere('(b.endYear * 1000 + b.endDay) <= :endKey')
                ->setParameter('endKey', $endKey);
        }

        if ($filters['campaign'] !== null) {
            $qb->andWhere('c.id = :campaign')
                ->setParameter('campaign', (int) $filters['campaign']);
        }

        $qb->orderBy('b.startYear', 'DESC')
            ->addOrderBy('b.startDay', 'DESC');

        $query = $qb->getQuery()
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $paginator = new Paginator($query);

        return [
            'items' => iterator_to_array($paginator),
            'total' => $paginator->count(),
        ];
    }

    // Date parsing centralized in ImperialDateHelper.
}
