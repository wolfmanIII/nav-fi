<?php

namespace App\Repository;

use App\Entity\Asset;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Asset>
 */
class AssetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Asset::class);
    }

    /**
     * @return Asset[]
     */
    public function findAllForUser(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function findOneForUser(int $id, User $user): ?Asset
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.id = :id')
            ->andWhere('a.user = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Asset[]
     */
    public function findWithoutCampaignForUser(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->andWhere('a.campaign IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array{name?: string, type_class?: string, campaign?: int} $filters
     *
     * @return array{items: Asset[], total: int}
     */
    public function findForUserWithFilters(User $user, array $filters, int $page, int $limit): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.campaign', 'c')
            ->addSelect('c')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user);

        if (!empty($filters['name'])) {
            $name = '%' . strtolower($filters['name']) . '%';
            $qb->andWhere('LOWER(a.name) LIKE :name')
                ->setParameter('name', $name);
        }

        if (!empty($filters['type_class'])) {
            $typeClass = '%' . strtolower($filters['type_class']) . '%';
            $qb->andWhere('LOWER(a.type) LIKE :typeClass OR LOWER(a.class) LIKE :typeClass')
                ->setParameter('typeClass', $typeClass);
        }

        if ($filters['campaign'] !== null) {
            $qb->andWhere('c.id = :campaign')
                ->setParameter('campaign', (int) $filters['campaign']);
        }

        if (!empty($filters['category'])) {
            $qb->andWhere('a.category = :category')
                ->setParameter('category', $filters['category']);
        }

        $qb->orderBy('a.name', 'ASC');

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
