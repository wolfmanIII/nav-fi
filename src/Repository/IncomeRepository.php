<?php

namespace App\Repository;

use App\Entity\Income;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Income>
 */
class IncomeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Income::class);
    }

    /**
     * @return Income[]
     */
    public function findAllForUser(User $user): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.user = :user')
            ->setParameter('user', $user)
            ->orderBy('i.signingYear', 'DESC')
            ->addOrderBy('i.signingDay', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findOneForUser(int $id, User $user): ?Income
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.id = :id')
            ->andWhere('i.user = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findAllNotCanceledForUser(User $user)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.user = :user')
            ->andWhere('i.cancelDay is null and i.cancelYear is null')
            ->setParameter('user', $user)
            ->orderBy('i.signingYear', 'DESC')
            ->addOrderBy('i.signingDay', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param array{title?: string, category?: int, asset?: int, campaign?: int} $filters
     *
     * @return array{items: Income[], total: int}
     */
    public function findForUserWithFilters(User $user, array $filters, int $page, int $limit): array
    {
        $qb = $this->createQueryBuilder('i')
            ->leftJoin('i.incomeCategory', 'cat')
            ->leftJoin('i.financialAccount', 'fa')
            ->leftJoin('fa.asset', 'a')
            ->leftJoin('a.campaign', 'camp')
            ->addSelect('cat', 'fa', 'a', 'camp')
            ->andWhere('i.user = :user')
            ->setParameter('user', $user);

        if (!empty($filters['title'])) {
            $title = '%' . strtolower($filters['title']) . '%';
            $qb->andWhere('LOWER(i.title) LIKE :title')
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

        $qb->orderBy('i.signingYear', 'DESC')
            ->addOrderBy('i.signingDay', 'DESC');

        $query = $qb->getQuery()
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $paginator = new Paginator($query);

        return [
            'items' => iterator_to_array($paginator),
            'total' => $paginator->count(),
        ];
    }
}
