<?php

namespace App\Repository;

use App\Entity\Crew;
use App\Entity\Asset;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Crew>
 */
class CrewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Crew::class);
    }

    /**
     * @return Crew[]
     */
    public function findAllForUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function findOneByCaptainOnAsset(Asset $asset, ?Crew $exclude = null): ?Crew
    {
        $qb = $this->createQueryBuilder('c')
            ->join('c.assetRoles', 'r')
            ->andWhere('c.asset = :asset')
            ->andWhere('r.code = :cap')
            ->setParameter('asset', $asset)
            ->setParameter('cap', 'CAP')
            ->setMaxResults(1);

        if ($exclude?->getId()) {
            $qb->andWhere('c.id != :exclude')
                ->setParameter('exclude', $exclude->getId());
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param array{search?: string, asset?: int, campaign?: int} $filters
     *
     * @return array{items: Crew[], total: int}
     */
    public function findForUserWithFilters(User $user, array $filters, int $page, int $limit): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.asset', 'a')
            ->leftJoin('a.campaign', 'camp')
            ->addSelect('a', 'camp')
            ->andWhere('c.user = :user')
            ->setParameter('user', $user);

        if (!empty($filters['search'])) {
            $search = '%' . strtolower($filters['search']) . '%';
            $qb->andWhere('LOWER(c.name) LIKE :search OR LOWER(c.surname) LIKE :search OR LOWER(CONCAT(c.name, \' \', c.surname)) LIKE :search')
                ->setParameter('search', $search);
        }

        if ($filters['asset'] !== null) {
            $qb->andWhere('a.id = :asset')
                ->setParameter('asset', (int) $filters['asset']);
        }

        if ($filters['campaign'] !== null) {
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

    /**
     * @param array{search?: string, nickname?: string} $filters
     *
     * @return array{items: Crew[], total: int}
     */
    public function findUnassignedForAsset(User $user, array $filters, int $page, int $limit, bool $needCaptain): array
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->andWhere('c.asset IS NULL')
            ->andWhere('(c.status IS NULL OR c.status NOT IN (:blockedStatus))')
            ->setParameter('user', $user);
        $qb->setParameter('blockedStatus', ['Missing (MIA)', 'Deceased']);

        if (!empty($filters['search'])) {
            $term = '%' . strtolower($filters['search']) . '%';
            $qb->andWhere('LOWER(c.name) LIKE :search OR LOWER(c.surname) LIKE :search OR LOWER(CONCAT(c.name, \' \', c.surname)) LIKE :search')
                ->setParameter('search', $term);
        }

        if (!empty($filters['nickname'])) {
            $nickname = '%' . strtolower($filters['nickname']) . '%';
            $qb->andWhere('LOWER(c.nickname) LIKE :nickname')
                ->setParameter('nickname', $nickname);
        }

        if (!$needCaptain) {
            $qb->leftJoin('c.assetRoles', 'capRole', 'WITH', 'capRole.code = :cap')
                ->andWhere('capRole.id IS NULL')
                ->setParameter('cap', 'CAP');
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

    public function findOneForUser(int $id, User $user): ?Crew
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.id = :id')
            ->andWhere('c.user = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
