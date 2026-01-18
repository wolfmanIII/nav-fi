<?php

namespace App\Repository;

use App\Entity\Salary;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Salary>
 */
class SalaryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Salary::class);
    }

    /**
     * @return Salary[]
     */
    public function findAllForUser(User $user): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.crew', 'c')
            ->join('c.asset', 'a')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function findOneForUser(int $id, User $user): ?Salary
    {
        return $this->createQueryBuilder('s')
            ->join('s.crew', 'c')
            ->join('c.asset', 'a')
            ->andWhere('s.id = :id')
            ->andWhere('a.user = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param array{search?: string, asset?: int, campaign?: int} $filters
     *
     * @return array{items: Salary[], total: int}
     */
    public function findForUserWithFilters(User $user, array $filters, int $page, int $limit): array
    {
        $qb = $this->createQueryBuilder('s')
            ->join('s.crew', 'c')
            ->leftJoin('c.asset', 'a')
            ->leftJoin('a.campaign', 'camp')
            ->addSelect('c', 'a', 'camp')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user);

        if (!empty($filters['search'])) {
            $search = '%' . strtolower($filters['search']) . '%';
            $qb->andWhere('LOWER(c.name) LIKE :search OR LOWER(c.surname) LIKE :search')
                ->setParameter('search', $search);
        }

        if (isset($filters['asset']) && $filters['asset'] !== null) {
            $qb->andWhere('a.id = :asset')
                ->setParameter('asset', (int) $filters['asset']);
        }

        if (isset($filters['campaign']) && $filters['campaign'] !== null) {
            $qb->andWhere('camp.id = :campaign')
                ->setParameter('campaign', (int) $filters['campaign']);
        }

        $qb->orderBy('c.surname', 'ASC')
            ->addOrderBy('c.name', 'ASC');

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
