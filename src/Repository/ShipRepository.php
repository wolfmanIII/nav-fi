<?php

namespace App\Repository;

use App\Entity\Ship;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ship>
 */
class ShipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ship::class);
    }

    /**
     * @return Ship[]
     */
    public function findAllForUser(User $user): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function findOneForUser(int $id, User $user): ?Ship
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.id = :id')
            ->andWhere('s.user = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Ship[]
     */
    public function findWithoutCampaignForUser(User $user): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.user = :user')
            ->andWhere('s.campaign IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array{name?: string, type_class?: string, campaign?: int} $filters
     *
     * @return array{items: Ship[], total: int}
     */
    public function findForUserWithFilters(User $user, array $filters, int $page, int $limit): array
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.campaign', 'c')
            ->addSelect('c')
            ->andWhere('s.user = :user')
            ->setParameter('user', $user);

        if (!empty($filters['name'])) {
            $name = '%'.strtolower($filters['name']).'%';
            $qb->andWhere('LOWER(s.name) LIKE :name')
                ->setParameter('name', $name);
        }

        if (!empty($filters['type_class'])) {
            $typeClass = '%'.strtolower($filters['type_class']).'%';
            $qb->andWhere('LOWER(s.type) LIKE :typeClass OR LOWER(s.class) LIKE :typeClass')
                ->setParameter('typeClass', $typeClass);
        }

        if ($filters['campaign'] !== null) {
            $qb->andWhere('c.id = :campaign')
                ->setParameter('campaign', (int) $filters['campaign']);
        }

        $qb->orderBy('s.name', 'ASC');

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
