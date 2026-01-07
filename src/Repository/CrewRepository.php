<?php

namespace App\Repository;

use App\Entity\Crew;
use App\Entity\Ship;
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

    public function findOneByCaptainOnShip(Ship $ship, ?Crew $exclude = null): ?Crew
    {
        $qb = $this->createQueryBuilder('c')
            ->join('c.shipRoles', 'r')
            ->andWhere('c.ship = :ship')
            ->andWhere('r.code = :cap')
            ->setParameter('ship', $ship)
            ->setParameter('cap', 'CAP')
            ->setMaxResults(1);

        if ($exclude?->getId()) {
            $qb->andWhere('c.id != :exclude')
                ->setParameter('exclude', $exclude->getId());
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param array{name?: string, surname?: string, ship?: int, campaign?: int} $filters
     *
     * @return array{items: Crew[], total: int}
     */
    public function findForUserWithFilters(User $user, array $filters, int $page, int $limit): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.ship', 's')
            ->leftJoin('s.campaign', 'camp')
            ->addSelect('s', 'camp')
            ->andWhere('c.user = :user')
            ->setParameter('user', $user);

        if (!empty($filters['name'])) {
            $name = '%'.strtolower($filters['name']).'%';
            $qb->andWhere('LOWER(c.name) LIKE :name OR LOWER(c.surname) LIKE :name OR LOWER(CONCAT(c.name, \' \', c.surname)) LIKE :name')
                ->setParameter('name', $name);
        }

        if (!empty($filters['surname'])) {
            $surname = '%'.strtolower($filters['surname']).'%';
            $qb->andWhere('LOWER(c.surname) LIKE :surname OR LOWER(CONCAT(c.name, \' \', c.surname)) LIKE :surname')
                ->setParameter('surname', $surname);
        }

        if ($filters['ship'] !== null) {
            $qb->andWhere('s.id = :ship')
                ->setParameter('ship', (int) $filters['ship']);
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
     * @param bool $needCaptain
     * @return array
     */
    public function getCrewNotInAnyShip(bool $needCaptain, ?User $user = null): array
    {
        $crew = $this->createQueryBuilder('c')
            ->join('c.shipRoles', 'r')
            ->where('c.ship IS NULL')
            ->andWhere($user ? 'c.user = :user' : '1 = 1')
            ->setParameter('user', $user)
            ->getQuery()->getResult();

        $result = [];
        /** @var Crew $c */
        foreach($crew as $c) {
            if (!$needCaptain) {
                if ($c->isCaptain()) {
                    continue;
                }
            }
            $result[] = $c;
        }

        return $result;
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
