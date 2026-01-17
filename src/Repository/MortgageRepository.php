<?php

namespace App\Repository;

use App\Entity\Mortgage;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Mortgage>
 */
class MortgageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Mortgage::class);
    }

    /**
     * @return Mortgage[]
     */
    public function findAllForUser(User $user): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function findOneForUser(int $id, User $user): ?Mortgage
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.id = :id')
            ->andWhere('m.user = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param array{name?: string, asset?: int, campaign?: int} $filters
     *
     * @return array{items: Mortgage[], total: int}
     */
    public function findForUserWithFilters(User $user, array $filters, int $page, int $limit): array
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.asset', 'a')
            ->leftJoin('a.campaign', 'c')
            ->addSelect('a', 'c')
            ->andWhere('m.user = :user')
            ->setParameter('user', $user);

        if (!empty($filters['name'])) {
            $name = '%' . strtolower($filters['name']) . '%';
            $qb->andWhere('LOWER(m.name) LIKE :name')
                ->setParameter('name', $name);
        }

        if ($filters['asset'] !== null) {
            $qb->andWhere('a.id = :asset')
                ->setParameter('asset', (int) $filters['asset']);
        }

        if ($filters['campaign'] !== null) {
            $qb->andWhere('c.id = :campaign')
                ->setParameter('campaign', (int) $filters['campaign']);
        }

        $qb->orderBy('m.name', 'ASC');

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
