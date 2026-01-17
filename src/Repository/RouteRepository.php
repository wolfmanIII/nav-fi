<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Route;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Route>
 */
class RouteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Route::class);
    }

    /**
     * @return Route[]
     */
    public function findAllForUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.asset', 'a')
            ->leftJoin('r.campaign', 'c')
            ->addSelect('a', 'c')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.plannedAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findOneForUser(int $id, User $user): ?Route
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.asset', 'a')
            ->leftJoin('r.campaign', 'c')
            ->addSelect('a', 'c')
            ->andWhere('r.id = :id')
            ->andWhere('a.user = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findOneForUserWithWaypoints(int $id, User $user): ?Route
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.asset', 'a')
            ->leftJoin('r.campaign', 'c')
            ->leftJoin('r.waypoints', 'w')
            ->addSelect('a', 'c', 'w')
            ->andWhere('r.id = :id')
            ->andWhere('a.user = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user)
            ->orderBy('w.position', 'ASC')
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @param array{name?: string, asset?: int, campaign?: int} $filters
     *
     * @return array{items: Route[], total: int}
     */
    public function findForUserWithFilters(User $user, array $filters, int $page, int $limit): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.asset', 'a')
            ->leftJoin('r.campaign', 'c')
            ->addSelect('a', 'c')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user);

        if (!empty($filters['name'])) {
            $term = '%' . strtolower($filters['name']) . '%';
            $qb->andWhere('LOWER(r.name) LIKE :name')
                ->setParameter('name', $term);
        }

        if ($filters['asset'] !== null) {
            $qb->andWhere('a.id = :asset')
                ->setParameter('asset', (int) $filters['asset']);
        }

        if ($filters['campaign'] !== null) {
            $qb->andWhere('c.id = :campaign')
                ->setParameter('campaign', (int) $filters['campaign']);
        }

        $qb->orderBy('r.plannedAt', 'DESC');

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
